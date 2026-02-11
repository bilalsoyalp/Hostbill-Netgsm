<?php

/**
 * NetGSM SMS Logs Plugin for HostBill
 * 
 * Bu plugin, NetGSM SMS modülü tarafından gönderilen SMS'lerin
 * loglarını admin panelinde görüntülemeyi sağlar.
 *
 * @package HostBill
 * @subpackage Other
 * @version 1.0.0
 * @author Özkan Kutuk <ozkan@edsoft.net>
 * @copyright 2026 Özkan Kutuk
 * @license MIT
 */

class Netgsm_Smslogs extends OtherModule
{
    /**
     * Module version
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Module name, visible in admin portal
     * @var string
     */
    protected $modname = 'Gönderilen SMS\'ler';

    /**
     * Module description
     * @var string
     */
    protected $description = 'NetGSM SMS modülü ile gönderilen SMS\'lerin loglarını görüntüler.';

    /**
     * Module info array
     * @var array
     */
    protected $info = [
        'haveadmin' => true,
        'haveuser' => false,
        'havetpl' => true,
        'haveapi' => false,
        'havecron' => false,
        'isobserver' => false,
        'extras_menu' => true
    ];

    /**
     * Database connection
     * @var PDO
     */
    protected $db;

    /**
     * Log table name
     * @var string
     */
    private $logTable = 'hb_netgsm_sms_logs';

    /**
     * Install module
     */
    public function install()
    {
        // Log tablosu NetGSM notification modülü tarafından oluşturuluyor
    }

    /**
     * Uninstall module
     */
    public function uninstall()
    {
        // Log tablosunu silme, notification modülü tarafından yönetiliyor
    }

    /**
     * Get SMS logs with pagination
     * 
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return array
     */
    public function getSmsLogs($limit = 50, $offset = 0, $filters = [])
    {
        $tableName = $this->logTable;
        
        $where = [];
        $params = [];
        
        if (!empty($filters['type'])) {
            $where[] = '`type` = :type';
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = '`status` = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = '`date` >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = '`date` <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['phone'])) {
            $where[] = '`phone` LIKE :phone';
            $params[':phone'] = '%' . $filters['phone'] . '%';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM `{$tableName}` {$whereClause} ORDER BY `date` DESC LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get total count of SMS logs
     * 
     * @param array $filters
     * @return int
     */
    public function getSmsLogsCount($filters = [])
    {
        $tableName = $this->logTable;
        
        $where = [];
        $params = [];
        
        if (!empty($filters['type'])) {
            $where[] = '`type` = :type';
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = '`status` = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = '`date` >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = '`date` <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['phone'])) {
            $where[] = '`phone` LIKE :phone';
            $params[':phone'] = '%' . $filters['phone'] . '%';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) FROM `{$tableName}` {$whereClause}";
        
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get statistics
     * 
     * @return array
     */
    public function getStatistics()
    {
        $tableName = $this->logTable;
        
        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'today' => 0,
            'today_success' => 0,
            'today_failed' => 0
        ];
        
        try {
            // Toplam
            $stats['total'] = (int) $this->db->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
            $stats['success'] = (int) $this->db->query("SELECT COUNT(*) FROM `{$tableName}` WHERE `status` = 'success'")->fetchColumn();
            $stats['failed'] = (int) $this->db->query("SELECT COUNT(*) FROM `{$tableName}` WHERE `status` = 'failed'")->fetchColumn();
            
            // Bugün
            $today = date('Y-m-d');
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$tableName}` WHERE DATE(`date`) = :today");
            $stmt->execute([':today' => $today]);
            $stats['today'] = (int) $stmt->fetchColumn();
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$tableName}` WHERE DATE(`date`) = :today AND `status` = 'success'");
            $stmt->execute([':today' => $today]);
            $stats['today_success'] = (int) $stmt->fetchColumn();
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `{$tableName}` WHERE DATE(`date`) = :today AND `status` = 'failed'");
            $stmt->execute([':today' => $today]);
            $stats['today_failed'] = (int) $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            // Ignore errors
        }
        
        return $stats;
    }

    /**
     * Delete old logs
     * 
     * @param int $days Days to keep
     * @return int Number of deleted rows
     */
    public function deleteOldLogs($days = 90)
    {
        $tableName = $this->logTable;
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        try {
            $stmt = $this->db->prepare("DELETE FROM `{$tableName}` WHERE `date` < :cutoff");
            $stmt->execute([':cutoff' => $cutoffDate]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
