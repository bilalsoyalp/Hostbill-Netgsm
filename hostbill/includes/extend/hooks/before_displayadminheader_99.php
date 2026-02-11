<?php
/**
 * Hook: Add "Gönderilen SMS'ler" link to Logs menu in HostBill Admin Panel
 * 
 * File location: includes/extend/hooks/before_displayadminheader_99.php
 * 
 * @package HostBill
 * @subpackage Extend
 * @version 1.0.0
 * @author Özkan Kutuk <ozkan@edsoft.net>
 * @copyright 2026 Özkan Kutuk
 * @license MIT
 */

echo <<<'JS'
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        // Only run on logs related pages
        var currentUrl = window.location.href;
        if (currentUrl.indexOf('cmd=logs') === -1 && 
            currentUrl.indexOf('cmd=emails') === -1 && 
            currentUrl.indexOf('cmd=gtwlog') === -1 &&
            currentUrl.indexOf('cmd=queue') === -1 &&
            currentUrl.indexOf('cmd=clientcredit') === -1 &&
            currentUrl.indexOf('cmd=portal_notifications') === -1) {
            return;
        }
        
        // Check if already added
        if (document.querySelector('a[href*="netgsm_smslogs"]')) {
            return;
        }
        
        // Find the leftNav container
        var leftNav = document.querySelector('td.leftNav');
        
        if (leftNav) {
            // Create menu item
            var menuItem = document.createElement('a');
            menuItem.href = '?cmd=netgsm_smslogs';
            menuItem.className = 'tstyled';
            menuItem.textContent = 'Gönderilen SMS\'ler';
            
            // Find "File access log" link and insert after it
            var fileAccessLog = leftNav.querySelector('a[href*="fileaccess"]');
            if (fileAccessLog) {
                fileAccessLog.parentNode.insertBefore(menuItem, fileAccessLog.nextSibling);
            } else {
                leftNav.appendChild(menuItem);
            }
        }
    }, 100);
});
</script>
JS;
