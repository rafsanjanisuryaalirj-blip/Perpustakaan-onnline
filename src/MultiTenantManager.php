<?php

/**
 * MultiTenantManager.php
 * 
 * Core functions untuk multi-tenant system.
 * Menangani:
 * - School status checking
 * - School data isolation
 * - Session validation
 * - Activation code management
 * - Trust score calculation
 */

class MultiTenantManager
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get school status
     * @return string 'trial', 'active', 'suspended'
     */
    public function getSchoolStatus($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT status FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        $result = $stmt->fetch();
        return $result ? $result['status'] : null;
    }

    /**
     * Check if school is in trial mode
     */
    public function isSchoolTrial($school_id)
    {
        return $this->getSchoolStatus($school_id) === 'trial';
    }

    /**
     * Check if school is active
     */
    public function isSchoolActive($school_id)
    {
        return $this->getSchoolStatus($school_id) === 'active';
    }

    /**
     * Check if school is suspended
     */
    public function isSchoolSuspended($school_id)
    {
        return $this->getSchoolStatus($school_id) === 'suspended';
    }

    /**
     * Get complete school data
     */
    public function getSchool($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, 
                   COUNT(DISTINCT b.id) as book_count,
                   COUNT(DISTINCT m.id) as student_count,
                   COUNT(DISTINCT bw.id) as borrow_count
            FROM schools s
            LEFT JOIN books b ON s.id = b.school_id
            LEFT JOIN members m ON s.id = m.school_id
            LEFT JOIN borrows bw ON s.id = bw.school_id
            WHERE s.id = :id
            GROUP BY s.id
        ');
        $stmt->execute(['id' => $school_id]);
        return $stmt->fetch();
    }

    /**
     * Get activation code (masking last 9 digits)
     * Display format: ****-****-XXXX (3 digit akhir)
     */
    public function getActivationCodeMasked($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT code FROM activation_codes WHERE school_id = :school_id AND is_active = TRUE LIMIT 1');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        $code = $result['code'];
        $visible = substr($code, -3);
        return '****-****-' . $visible;
    }

    /**
     * Get full activation code (untuk internal use only, jangan exposed)
     */
    public function getActivationCodeFull($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT code FROM activation_codes WHERE school_id = :school_id AND is_active = TRUE LIMIT 1');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();
        return $result ? $result['code'] : null;
    }

    /**
     * Verify activation code (for student registration)
     * @param $school_id
     * @param $code - code to verify
     * @return bool - true if valid
     */
    public function verifyActivationCode($school_id, $code)
    {
        $stmt = $this->pdo->prepare('
            SELECT ac.id FROM activation_codes ac
            WHERE ac.school_id = :school_id 
            AND ac.code = :code 
            AND ac.is_active = TRUE
            LIMIT 1
        ');
        $stmt->execute(['school_id' => $school_id, 'code' => $code]);
        return $stmt->fetch() !== false;
    }

    /**
     * Regenerate activation code (invalidate old, create new)
     */
    public function regenerateActivationCode($school_id, $user_id = null)
    {
        try {
            $this->pdo->beginTransaction();

            // Invalidate old code
            $stmt = $this->pdo->prepare('UPDATE activation_codes SET is_active = FALSE WHERE school_id = :school_id');
            $stmt->execute(['school_id' => $school_id]);

            // Generate new code
            $newCode = $this->generateActivationCode();

            // Insert new code
            $stmt = $this->pdo->prepare('
                INSERT INTO activation_codes (school_id, code, regenerated_by)
                VALUES (:school_id, :code, :user_id)
            ');
            $stmt->execute([
                'school_id' => $school_id,
                'code' => $newCode,
                'user_id' => $user_id
            ]);

            $this->pdo->commit();
            return $newCode;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Generate random activation code (12 alphanumeric)
     * Format: ABC1-DEF2-GHI3
     */
    private function generateActivationCode()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
    }

    /**
     * Check if trial has expired (14 days)
     */
    public function isTrialExpired($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT trial_started_at FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        $result = $stmt->fetch();

        if (!$result) {
            return false;
        }

        $trialStarted = strtotime($result['trial_started_at']);
        $trialExpiry = $trialStarted + (14 * 24 * 60 * 60); // 14 days

        return time() > $trialExpiry;
    }

    /**
     * Get days remaining in trial
     */
    public function getTrialDaysRemaining($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT trial_started_at FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        $trialStarted = strtotime($result['trial_started_at']);
        $trialExpiry = $trialStarted + (14 * 24 * 60 * 60);
        $daysRemaining = ceil(($trialExpiry - time()) / (24 * 60 * 60));

        return max(0, $daysRemaining);
    }

    /**
     * Request activation (sekolah mengajukan aktivasi)
     * Update trust_score dan tanggal request
     */
    public function requestActivation($school_id, $user_id = null)
    {
        try {
            $this->pdo->beginTransaction();

            // Update school
            $stmt = $this->pdo->prepare('
                UPDATE schools 
                SET activation_requested_at = CURRENT_TIMESTAMP,
                    activation_requested_by = :user_id
                WHERE id = :school_id
            ');
            $stmt->execute(['school_id' => $school_id, 'user_id' => $user_id]);

            // Log activity
            $this->logActivity($school_id, 'activation_request', 'submit', 1, $user_id);

            // Recalculate trust score
            $this->recalculateTrustScore($school_id);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get trust score untuk school
     */
    public function getTrustScore($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT total_score FROM trust_scores WHERE school_id = :school_id');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();
        return $result ? $result['total_score'] : 0;
    }

    /**
     * Recalculate trust score (comprehensive)
     * Based on factors defined in trust_score_factors table
     */
    public function recalculateTrustScore($school_id)
    {
        $score = 0;
        $factors = [];

        // 1. Activation requested?
        $stmt = $this->pdo->prepare('SELECT activation_requested_at FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        $school = $stmt->fetch();

        if ($school['activation_requested_at']) {
            $score += 10;
            $factors['activation_requested'] = 10;
        }

        // 2. Email admin .sch.id?
        $stmt = $this->pdo->prepare('
            SELECT email FROM users 
            WHERE school_id = :school_id AND role = "admin" 
            LIMIT 1
        ');
        $stmt->execute(['school_id' => $school_id]);
        $admin = $stmt->fetch();

        if ($admin && strpos($admin['email'], '.sch.id') !== false) {
            $score += 15;
            $factors['email_sch_id'] = 15;
        }

        // 3. Activation code entered (dalam last 30 hari)?
        $stmt = $this->pdo->prepare('
            SELECT regenerated_at FROM activation_codes 
            WHERE school_id = :school_id AND is_active = TRUE
            AND (regenerated_at IS NOT NULL OR generated_at > DATE_SUB(NOW(), INTERVAL 30 DAY))
        ');
        $stmt->execute(['school_id' => $school_id]);
        if ($stmt->fetch()) {
            $score += 20;
            $factors['activation_code_entered'] = 20;
        }

        // 4. Normal activity (no anomalies)?
        $anomalies = $this->checkAnomalies($school_id);
        if (!$anomalies) {
            $score += 25;
            $factors['normal_activity'] = 25;
        } else {
            // Apply penalties
            foreach ($anomalies as $anomaly) {
                $score -= $anomaly['penalty'];
            }
        }

        // 5. Trial duration > 7 hari?
        $daysOld = $this->getSchoolAgeInDays($school_id);
        if ($daysOld > 7) {
            $score += 10;
            $factors['trial_duration'] = 10;
        }

        // 6. Min 5 transaksi?
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM borrows WHERE school_id = :school_id');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();
        if ($result['count'] >= 5) {
            $score += 10;
            $factors['min_transactions'] = 10;
        }

        // 7. Email verified?
        $stmt = $this->pdo->prepare('SELECT email FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        $school = $stmt->fetch();
        if ($school['email']) {
            $score += 5;
            $factors['email_verified'] = 5;
        }

        // Clamp score to max 95
        $score = min($score, 95);

        // Update or insert trust_scores record
        $stmt = $this->pdo->prepare('
            INSERT INTO trust_scores (school_id, total_score, factors, last_updated_by)
            VALUES (:school_id, :score, :factors, "system")
            ON DUPLICATE KEY UPDATE 
                total_score = VALUES(total_score),
                factors = VALUES(factors),
                calculated_at = CURRENT_TIMESTAMP,
                last_updated_by = "system"
        ');
        $stmt->execute([
            'school_id' => $school_id,
            'score' => $score,
            'factors' => json_encode($factors)
        ]);

        // Log history
        $oldScore = $this->getTrustScore($school_id);
        if ($oldScore !== $score) {
            $stmt = $this->pdo->prepare('
                INSERT INTO trust_score_history (school_id, old_score, new_score, reason, triggered_by)
                VALUES (:school_id, :old_score, :new_score, :reason, "recalculation")
            ');
            $stmt->execute([
                'school_id' => $school_id,
                'old_score' => $oldScore,
                'new_score' => $score,
                'reason' => 'Recalculation from factors: ' . implode(', ', array_keys($factors))
            ]);
        }

        // Check if score >= 70, then auto-activate
        if ($score >= 70 && $this->isSchoolTrial($school_id)) {
            $this->autoActivateSchool($school_id);
        }

        return $score;
    }

    /**
     * Check if school has anomalies (for trust score penalty)
     */
    private function checkAnomalies($school_id)
    {
        $anomalies = [];

        // Check 1: Bulk upload > 100 books dalam 1 jam
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count FROM school_activities
            WHERE school_id = :school_id 
            AND activity_type = "book_creation"
            AND recorded_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();
        if ($result['count'] > 100) {
            $anomalies[] = ['type' => 'bulk_book_upload', 'penalty' => 20];
        }

        // Check 2: Delete masif > 50 dalam 1 hari
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count FROM school_activities
            WHERE school_id = :school_id 
            AND action = "delete"
            AND recorded_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();
        if ($result['count'] > 50) {
            $anomalies[] = ['type' => 'bulk_delete', 'penalty' => 25];
        }

        // Check 3: Login failed > 10x (dalam table users atau activity log?)
        // Implementasi sesuai kebutuhan

        return $anomalies;
    }

    /**
     * Get school age in days
     */
    private function getSchoolAgeInDays($school_id)
    {
        $stmt = $this->pdo->prepare('SELECT created_at FROM schools WHERE id = :id');
        $stmt->execute(['id' => $school_id]);
        $result = $stmt->fetch();

        if (!$result) {
            return 0;
        }

        $createdAt = strtotime($result['created_at']);
        $days = floor((time() - $createdAt) / (24 * 60 * 60));

        return $days;
    }

    /**
     * Auto-activate school (triggered by trust score >= 70)
     */
    private function autoActivateSchool($school_id)
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                UPDATE schools SET status = "active" WHERE id = :school_id AND status = "trial"
            ');
            $stmt->execute(['school_id' => $school_id]);

            // Log to history
            $stmt = $this->pdo->prepare('
                INSERT INTO trust_score_history (school_id, new_score, reason, triggered_by)
                VALUES (:school_id, 70, :reason, "auto_activation")
            ');
            $stmt->execute([
                'school_id' => $school_id,
                'reason' => 'Auto-activated: trust score >= 70'
            ]);

            $this->logActivity($school_id, 'school_activation', 'auto_activate', 1);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Manually suspend school (admin action)
     */
    public function suspendSchool($school_id, $reason, $admin_id = null)
    {
        $stmt = $this->pdo->prepare('
            UPDATE schools SET status = "suspended", admin_notes = :reason WHERE id = :school_id
        ');
        $stmt->execute(['school_id' => $school_id, 'reason' => $reason]);

        $this->logActivity($school_id, 'school_suspension', 'suspend', 1, $admin_id);
        return true;
    }

    /**
     * Manually reactivate suspended school
     */
    public function reactivateSchool($school_id, $reason, $admin_id = null)
    {
        $stmt = $this->pdo->prepare('
            UPDATE schools SET status = "active", admin_notes = :reason WHERE id = :school_id
        ');
        $stmt->execute(['school_id' => $school_id, 'reason' => $reason]);

        $this->logActivity($school_id, 'school_reactivation', 'reactivate', 1, $admin_id);
        return true;
    }

    /**
     * Log school activity (untuk tracking & anomaly detection)
     */
    public function logActivity($school_id, $activity_type, $action, $count = 1, $user_id = null)
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO school_activities 
            (school_id, activity_type, action, data_count, user_id, ip_address)
            VALUES (:school_id, :activity_type, :action, :count, :user_id, :ip)
        ');
        $stmt->execute([
            'school_id' => $school_id,
            'activity_type' => $activity_type,
            'action' => $action,
            'count' => $count,
            'user_id' => $user_id,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
        ]);
    }
}

?>