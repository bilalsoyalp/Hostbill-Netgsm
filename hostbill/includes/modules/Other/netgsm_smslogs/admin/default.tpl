{*
 * NetGSM SMS Logs Admin Template
 * 
 * @package HostBill
 * @subpackage Other
 * @version 1.0.0
 * @author Özkan Kutuk <ozkan@edsoft.net>
 * @copyright 2026 Özkan Kutuk
 * @license MIT
 *}
<div id="newshelfnav" class="newhorizontalnav" style="margin-top: -32px; position: relative;">
    <div class="list-1">
        <ul>
            <li class="active last">
                <a href="?cmd={$modulename}"><span><i class="fa fa-comments"></i> Gönderilen SMS'ler</span></a>
            </li>
        </ul>
    </div>
</div>

{literal}
<style>
.sms-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.sms-stat-box {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px 25px;
    min-width: 150px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.sms-stat-box .number {
    font-size: 28px;
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
}
.sms-stat-box .label {
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
}
.sms-stat-box.success .number { color: #28a745; }
.sms-stat-box.failed .number { color: #dc3545; }
.sms-stat-box.total .number { color: #007bff; }
.sms-stat-box.today .number { color: #6f42c1; }

.sms-filters {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.sms-filters form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: flex-end;
}
.sms-filters .filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.sms-filters label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}
.sms-filters input, .sms-filters select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
.sms-filters input[type="text"] { width: 150px; }
.sms-filters input[type="date"] { width: 140px; }

.sms-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.sms-table th, .sms-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.sms-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    font-size: 13px;
    text-transform: uppercase;
}
.sms-table tr:hover {
    background: #f8f9fa;
}
.sms-table .status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.sms-table .status-success {
    background: #d4edda;
    color: #155724;
}
.sms-table .status-failed {
    background: #f8d7da;
    color: #721c24;
}
.sms-table .type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}
.sms-table .type-admin { background: #cce5ff; color: #004085; }
.sms-table .type-client { background: #d4edda; color: #155724; }
.sms-table .type-test { background: #fff3cd; color: #856404; }
.sms-table .type-unknown { background: #e2e3e5; color: #383d41; }

.sms-table .message-cell {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.sms-table .message-cell:hover {
    white-space: normal;
    word-break: break-word;
}
.sms-table .date-cell {
    white-space: nowrap;
    font-size: 13px;
    color: #666;
}
.sms-table .api-response {
    font-size: 12px;
    color: #888;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}
.pagination-info {
    color: #666;
    font-size: 14px;
}
.pagination-links {
    display: flex;
    gap: 5px;
}
.pagination-links a, .pagination-links span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
}
.pagination-links a:hover {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}
.pagination-links .current {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}
.empty-state i {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 15px;
}
.empty-state h3 {
    margin: 0 0 10px;
    color: #333;
}

.delete-old-form {
    margin-top: 20px;
    padding: 15px;
    background: #fff3cd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.delete-old-form label {
    color: #856404;
}
</style>
{/literal}

<div style="padding: 20px;">
    <h2 style="margin: 0 0 20px; color: #333;">
        <i class="fa fa-comments"></i> Gönderilen SMS'ler
    </h2>
    
    {if $flash_message}
        <div class="alert alert-success" style="padding: 15px; background: #d4edda; border-radius: 8px; margin-bottom: 20px; color: #155724;">
            {$flash_message}
        </div>
    {/if}
    
    <!-- İstatistikler -->
    <div class="sms-stats">
        <div class="sms-stat-box total">
            <span class="number">{$stats.total|default:0}</span>
            <span class="label">Toplam SMS</span>
        </div>
        <div class="sms-stat-box success">
            <span class="number">{$stats.success|default:0}</span>
            <span class="label">Başarılı</span>
        </div>
        <div class="sms-stat-box failed">
            <span class="number">{$stats.failed|default:0}</span>
            <span class="label">Başarısız</span>
        </div>
        <div class="sms-stat-box today">
            <span class="number">{$stats.today|default:0}</span>
            <span class="label">Bugün</span>
        </div>
        <div class="sms-stat-box success">
            <span class="number">{$stats.today_success|default:0}</span>
            <span class="label">Bugün Başarılı</span>
        </div>
        <div class="sms-stat-box failed">
            <span class="number">{$stats.today_failed|default:0}</span>
            <span class="label">Bugün Başarısız</span>
        </div>
    </div>
    
    <!-- Filtreler -->
    <div class="sms-filters">
        <form method="get" action="">
            <input type="hidden" name="cmd" value="{$modulename}">
            
            <div class="filter-group">
                <label>Tip</label>
                <select name="type">
                    <option value="">Tümü</option>
                    <option value="admin" {if $filters.type eq 'admin'}selected{/if}>Admin</option>
                    <option value="client" {if $filters.type eq 'client'}selected{/if}>Müşteri</option>
                    <option value="test" {if $filters.type eq 'test'}selected{/if}>Test</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Durum</label>
                <select name="status">
                    <option value="">Tümü</option>
                    <option value="success" {if $filters.status eq 'success'}selected{/if}>Başarılı</option>
                    <option value="failed" {if $filters.status eq 'failed'}selected{/if}>Başarısız</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Başlangıç Tarihi</label>
                <input type="date" name="date_from" value="{$filters.date_from|default:''}">
            </div>
            
            <div class="filter-group">
                <label>Bitiş Tarihi</label>
                <input type="date" name="date_to" value="{$filters.date_to|default:''}">
            </div>
            
            <div class="filter-group">
                <label>Telefon</label>
                <input type="text" name="phone" value="{$filters.phone|default:''}" placeholder="Telefon ara...">
            </div>
            
            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary" style="padding: 8px 20px;">
                    <i class="fa fa-search"></i> Filtrele
                </button>
            </div>
            
            {if $filters|@count > 0}
            <div class="filter-group">
                <label>&nbsp;</label>
                <a href="?cmd={$modulename}" class="btn btn-default" style="padding: 8px 20px; text-decoration: none;">
                    <i class="fa fa-times"></i> Temizle
                </a>
            </div>
            {/if}
        </form>
    </div>
    
    <!-- SMS Listesi -->
    {if $logs|@count > 0}
        <table class="sms-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 150px;">Tarih</th>
                    <th style="width: 80px;">Tip</th>
                    <th style="width: 100px;">Telefon</th>
                    <th>Mesaj</th>
                    <th style="width: 90px;">Durum</th>
                    <th style="width: 200px;">API Yanıtı</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$logs item=log}
                <tr>
                    <td>{$log.id}</td>
                    <td class="date-cell">{$log.date}</td>
                    <td>
                        <span class="type-badge type-{$log.type|default:'unknown'}">
                            {if $log.type eq 'admin'}Admin
                            {elseif $log.type eq 'client'}Müşteri
                            {elseif $log.type eq 'test'}Test
                            {else}{$log.type}{/if}
                        </span>
                    </td>
                    <td>{$log.phone}</td>
                    <td class="message-cell" title="{$log.message|escape:'html'}">{$log.message}</td>
                    <td>
                        <span class="status-badge status-{$log.status}">
                            {if $log.status eq 'success'}Başarılı{else}Başarısız{/if}
                        </span>
                    </td>
                    <td class="api-response" title="{$log.api_response|escape:'html'}">
                        {if $log.api_code}<strong>[{$log.api_code}]</strong>{/if}
                        {$log.api_response}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        
        <!-- Pagination -->
        {if $totalPages gt 1}
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Toplam {$totalCount} kayıttan {$showFrom} - {$showTo} arası gösteriliyor
            </div>
            <div class="pagination-links">
                {if $currentPage gt 1}
                    <a href="?cmd={$modulename}&page=1{if $filters.type}&type={$filters.type}{/if}{if $filters.status}&status={$filters.status}{/if}{if $filters.date_from}&date_from={$filters.date_from}{/if}{if $filters.date_to}&date_to={$filters.date_to}{/if}{if $filters.phone}&phone={$filters.phone}{/if}">&laquo; İlk</a>
                    <a href="?cmd={$modulename}&page={$prevPage}{if $filters.type}&type={$filters.type}{/if}{if $filters.status}&status={$filters.status}{/if}{if $filters.date_from}&date_from={$filters.date_from}{/if}{if $filters.date_to}&date_to={$filters.date_to}{/if}{if $filters.phone}&phone={$filters.phone}{/if}">&lsaquo; Önceki</a>
                {/if}
                
                {foreach from=$pageNumbers item=pageNum}
                    {if $pageNum eq $currentPage}
                        <span class="current">{$pageNum}</span>
                    {else}
                        <a href="?cmd={$modulename}&page={$pageNum}{if $filters.type}&type={$filters.type}{/if}{if $filters.status}&status={$filters.status}{/if}{if $filters.date_from}&date_from={$filters.date_from}{/if}{if $filters.date_to}&date_to={$filters.date_to}{/if}{if $filters.phone}&phone={$filters.phone}{/if}">{$pageNum}</a>
                    {/if}
                {/foreach}
                
                {if $currentPage lt $totalPages}
                    <a href="?cmd={$modulename}&page={$nextPage}{if $filters.type}&type={$filters.type}{/if}{if $filters.status}&status={$filters.status}{/if}{if $filters.date_from}&date_from={$filters.date_from}{/if}{if $filters.date_to}&date_to={$filters.date_to}{/if}{if $filters.phone}&phone={$filters.phone}{/if}">Sonraki &rsaquo;</a>
                    <a href="?cmd={$modulename}&page={$totalPages}{if $filters.type}&type={$filters.type}{/if}{if $filters.status}&status={$filters.status}{/if}{if $filters.date_from}&date_from={$filters.date_from}{/if}{if $filters.date_to}&date_to={$filters.date_to}{/if}{if $filters.phone}&phone={$filters.phone}{/if}">Son &raquo;</a>
                {/if}
            </div>
        </div>
        {/if}
        
        <!-- Eski Logları Sil -->
        <div class="delete-old-form">
            <form method="post" action="?cmd={$modulename}&action=delete_old" onsubmit="return confirm('Seçilen günden eski tüm loglar silinecek. Emin misiniz?');">
                <label><i class="fa fa-trash"></i> Eski logları temizle:</label>
                <select name="days" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                    <option value="30">30 günden eski</option>
                    <option value="60">60 günden eski</option>
                    <option value="90" selected>90 günden eski</option>
                    <option value="180">180 günden eski</option>
                    <option value="365">1 yıldan eski</option>
                </select>
                <button type="submit" class="btn btn-warning" style="padding: 8px 15px;">
                    <i class="fa fa-trash"></i> Sil
                </button>
            </form>
        </div>
        
    {else}
        <div class="empty-state">
            <i class="fa fa-comments"></i>
            <h3>Henüz SMS kaydı yok</h3>
            <p>SMS gönderildiğinde burada listelenecektir.</p>
        </div>
    {/if}
</div>
