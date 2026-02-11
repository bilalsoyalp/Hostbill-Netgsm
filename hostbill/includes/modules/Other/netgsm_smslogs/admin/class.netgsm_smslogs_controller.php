<?php

/**
 * NetGSM SMS Logs Admin Controller
 * 
 * Admin panelinde SMS loglarını görüntüler
 *
 * @package HostBill
 * @subpackage Other
 * @version 1.0.0
 * @author Özkan Kutuk <ozkan@edsoft.net>
 * @copyright 2026 Özkan Kutuk
 * @license MIT
 */
class netgsm_smslogs_controller extends HBController
{
    /**
     * Related module object
     * @var Netgsm_Smslogs $module
     */
    var $module;

    /**
     * Admin authorization object
     * @var AdminAuthorization
     */
    var $authorization;

    /**
     * Template object
     * @var Smarty $template
     */
    var $template;

    /**
     * Items per page
     * @var int
     */
    private $perPage = 50;

    /**
     * Called before any other method
     * @param array $params
     */
    public function beforeCall($params)
    {
        $modDir = strtolower($this->module->getModuleDirName());
        $this->template->pageTitle = $this->module->getModName();
        $this->template->module_template_dir = APPDIR_MODULES . 'Other' . DS . $modDir . DS . 'admin';
        $this->template->assign('moduleurl', Utilities::checkSecureURL(HBConfig::getConfig('InstallURL') . 'includes/modules/Other/' . $modDir . '/admin/'));
        $this->template->assign('modulename', $this->module->getModuleName());
        $this->template->assign('modname', $this->module->getModName());
        $this->template->assign('moduleid', $this->module->getModuleId());
        
        // Flash message
        $flashMessage = '';
        if (isset($_SESSION['flash_message'])) {
            $flashMessage = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
        }
        $this->template->assign('flash_message', $flashMessage);
        
        $this->template->showtpl = 'default';
    }

    /**
     * Default action - show SMS logs list
     * @param array $params
     */
    public function _default($params)
    {
        // Filtreler
        $filters = [];
        if (!empty($_GET['type'])) {
            $filters['type'] = $_GET['type'];
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        if (!empty($_GET['phone'])) {
            $filters['phone'] = $_GET['phone'];
        }
        
        // Pagination
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $this->perPage;
        
        // Verileri al
        $logs = $this->module->getSmsLogs($this->perPage, $offset, $filters);
        $totalCount = $this->module->getSmsLogsCount($filters);
        $totalPages = ceil($totalCount / $this->perPage);
        $stats = $this->module->getStatistics();
        
        // Pagination hesaplamaları
        $showFrom = ($page - 1) * $this->perPage + 1;
        $showTo = min($page * $this->perPage, $totalCount);
        $prevPage = $page - 1;
        $nextPage = $page + 1;
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        // Sayfa numaraları dizisi
        $pageNumbers = [];
        for ($i = $startPage; $i <= $endPage; $i++) {
            $pageNumbers[] = $i;
        }
        
        // Template'e ata
        $this->template->assign('logs', $logs);
        $this->template->assign('stats', $stats);
        $this->template->assign('filters', $filters);
        $this->template->assign('currentPage', $page);
        $this->template->assign('totalPages', $totalPages);
        $this->template->assign('totalCount', $totalCount);
        $this->template->assign('perPage', $this->perPage);
        $this->template->assign('showFrom', $showFrom);
        $this->template->assign('showTo', $showTo);
        $this->template->assign('prevPage', $prevPage);
        $this->template->assign('nextPage', $nextPage);
        $this->template->assign('pageNumbers', $pageNumbers);
    }

    /**
     * Delete old logs action
     * @param array $params
     */
    public function delete_old($params)
    {
        $days = isset($_POST['days']) ? (int) $_POST['days'] : 90;
        $deleted = $this->module->deleteOldLogs($days);
        
        $_SESSION['flash_message'] = "{$deleted} adet eski log kaydı silindi.";
        
        header('Location: ?cmd=' . $this->module->getModuleName());
        exit;
    }
}
