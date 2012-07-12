<?php
/**
 * Copyright (C) 2010  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Supplier Management section */

// main system configuration
require '../../../ucsysconfig.inc.php';
// start the session
require UCS_BASE_DIR.'admin/default/session.inc.php';
require UCS_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('master_file', 'r');
$can_write = utility::havePrivilege('master_file', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

/* RECORD OPERATION */
if (isset($_POST['saveData']) AND $can_read AND $can_write) {
    $supplierName = trim(strip_tags($_POST['supplierName']));
    // check form validity
    if (empty($supplierName)) {
        utility::jsAlert(__('Supplier Name can\'t be empty'));
        exit();
    } else {
        $data['supplier_name'] = $dbs->escape_string($supplierName);
        $data['address'] = trim($dbs->escape_string(strip_tags($_POST['supplierPlace'])));
        $data['contact'] = trim($dbs->escape_string(strip_tags($_POST['supplierContact'])));
        $data['phone'] = trim($dbs->escape_string(strip_tags($_POST['supplierPhone'])));
        $data['fax'] = trim($dbs->escape_string(strip_tags($_POST['supplierFax'])));
        $data['e_mail'] = trim($dbs->escape_string(strip_tags($_POST['supplierEmail'])));
        $data['account'] = trim($dbs->escape_string(strip_tags($_POST['supplierAccount'])));
        $data['input_date'] = date('Y-m-d');
        $data['last_update'] = date('Y-m-d');

        // create sql op object
        $sql_op = new simbio_dbop($dbs);
        if (isset($_POST['updateRecordID'])) {
            /* UPDATE RECORD MODE */
            // remove input date
            unset($data['input_date']);
            // filter update record ID
            $updateRecordID = (integer)$_POST['updateRecordID'];
            // update the data
            $update = $sql_op->update('mst_supplier', $data, 'supplier_id='.$updateRecordID);
            if ($update) {
                utility::jsAlert(__('Supplier Data Successfully Updated'));
                echo '<script type="text/javascript">parent.setContent(\'mainContent\', parent.getPreviousAJAXurl(), \'post\');</script>';
            } else { utility::jsAlert(__('Supplier Data FAILED to Updated. Please Contact System Administrator')."\nDEBUG : ".$error); }
            exit();
        } else {
            /* INSERT RECORD MODE */
            // insert the data
            $insert = $sql_op->insert('mst_supplier', $data);
            if ($insert) {
                utility::jsAlert(__('New Supplier Data Successfully Saved'));
                echo '<script type="text/javascript">parent.setContent(\'mainContent\', \''.$_SERVER['PHP_SELF'].'\', \'post\');</script>';
            } else {
                utility::jsAlert(__('Supplier Data FAILED to Save. Please Contact System Administrator')."\nDEBUG : ".$sql_op->error);
            }
            exit();
        }
    }
    exit();
} else if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!($can_read AND $can_write)) {
        die();
    }
    /* DATA DELETION PROCESS */
    $sql_op = new simbio_dbop($dbs);
    $failed_array = array();
    $error_num = 0;
    if (!is_array($_POST['itemID'])) {
        // make an array
        $_POST['itemID'] = array((integer)$_POST['itemID']);
    }
    // loop array
    foreach ($_POST['itemID'] as $itemID) {
        $itemID = (integer)$itemID;
        if (!$sql_op->delete('mst_supplier', 'supplier_id='.$itemID)) {
            $error_num++;
        }
    }

    // error alerting
    if ($error_num == 0) {
        utility::jsAlert(__('All Data Successfully Deleted'));
        echo '<script type="text/javascript">parent.setContent(\'mainContent\', \''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\', \'post\');</script>';
    } else {
        utility::jsAlert(__('Some or All Data NOT deleted successfully!\nPlease contact system administrator'));
        echo '<script type="text/javascript">parent.setContent(\'mainContent\', \''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\', \'post\');</script>';
    }
    exit();
}
/* RECORD OPERATION END */

/* search form */
?>
<fieldset class="menuBox">
<div class="menuBoxInner masterFileIcon">
    <?php echo strtoupper(__('Supplier')); ?> - <a href="<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/supplier.php?action=detail" class="headerText2"><?php echo __('Add New Supplier'); ?></a>
    &nbsp; <a href="<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/supplier.php" class="headerText2"><?php echo __('Supplier List'); ?></a>
    <hr />
    <form name="search" action="<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/supplier.php" id="search" method="get" style="display: inline;"><?php echo __('Search'); ?> :
    <input type="text" name="keywords" size="30" />
    <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="button" />
    </form>
</div>
</fieldset>
<?php
/* search form end */
/* main content */
if (isset($_POST['detail']) OR (isset($_GET['action']) AND $_GET['action'] == 'detail')) {
    if (!($can_read AND $can_write)) {
        die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
    }
    /* RECORD FORM */
    $itemID = (integer)isset($_POST['itemID'])?$_POST['itemID']:0;
    $rec_q = $dbs->query('SELECT * FROM mst_supplier WHERE supplier_id='.$itemID);
    $rec_d = $rec_q->fetch_assoc();

    // create new instance
    $form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'].'?itemID='.$itemID, 'post');
    $form->submit_button_attr = 'name="saveData" value="'.__('Save').'" class="button"';

    // form table attributes
    $form->table_attr = 'align="center" id="dataList" cellpadding="5" cellspacing="0"';
    $form->table_header_attr = 'class="alterCell" style="font-weight: bold;"';
    $form->table_content_attr = 'class="alterCell2"';

    // edit mode flag set
    if ($rec_q->num_rows > 0) {
        $form->edit_mode = true;
        // record ID for delete process
        $form->record_id = $itemID;
        // form record title
        $form->record_title = $rec_d['supplier_name'];
        // submit button attribute
        $form->submit_button_attr = 'name="saveData" value="'.__('Update').'" class="button"';
    }

    /* Form Element(s) */
    // supplier name
    $form->addTextField('text', 'supplierName', __('Supplier Name').'*', $rec_d['supplier_name'], 'style="width: 60%;"');
    // supplier address
    $form->addTextField('textarea', 'supplierPlace', __('Address'), $rec_d['address'], 'style="width: 100%;" rows="2"');
    // supplier contact
    $form->addTextField('text', 'supplierContact', __('Contact'), $rec_d['contact'], 'style="width: 60%;"');
    // supplier phone
    $form->addTextField('text', 'supplierPhone', __('Phone Number'), $rec_d['phone'], 'style="width: 60%;"');
    // supplier fax
    $form->addTextField('text', 'supplierFax', __('Fax Number'), $rec_d['fax'], 'style="width: 60%;"');
    // supplier account number
    $form->addTextField('text', 'supplierAccount', __('Account Number'), $rec_d['account'], 'style="width: 60%;"');

    // edit mode messagge
    if ($form->edit_mode) {
        echo '<div class="infoBox">'.__('You are going to edit Supplier data').' : <b>'.$rec_d['supplier_name'].'</b> <br />'.__('Last Update').$rec_d['last_update'].'</div>'; //mfc
    }
    // print out the form object
    echo $form->printOut();
} else {
    /* SUPPLIER LIST */
    // table spec
    $table_spec = 'mst_supplier AS sp';

    // create datagrid
    $datagrid = new simbio_datagrid();
    if ($can_read AND $can_write) {
        $datagrid->setSQLColumn('sp.supplier_id',
            'sp.supplier_name AS \''.__('Supplier Name').'\'',
            'sp.contact AS \''.__('Contact').'\'',
            'sp.phone AS \''.__('Phone Number').'\'',
            'sp.fax AS \''.__('Fax Number').'\'',
            'sp.last_update AS \''.__('Last Update').'\'');
    } else {
        $datagrid->setSQLColumn('sp.supplier_name AS \''.__('Supplier Name').'\'',
            'sp.contact AS \''.__('Contact').'\'',
            'sp.phone AS \''.__('Phone Number').'\'',
            'sp.fax AS \''.__('Fax Number').'\'',
            'sp.last_update AS \''.__('Last Update').'\'');
    }

    $datagrid->setSQLorder('supplier_name ASC');

    // is there any search
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
       $keywords = $dbs->escape_string($_GET['keywords']);
       $datagrid->setSQLCriteria("sp.supplier_name LIKE '%$keywords%' OR sp.supplier_id LIKE '%$keywords%'
            OR sp.contact LIKE '%$keywords%' OR sp.address LIKE '%$keywords%'");
    }

    // set table and table header attributes
    $datagrid->table_attr = 'align="center" id="dataList" cellpadding="5" cellspacing="0"';
    $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
    // set delete proccess URL
    $datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];

    // put the result into variables
    $datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, ($can_read AND $can_write));
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
        $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords')); //mfc
        echo '<div class="infoBox">'.$msg.' : "'.$_GET['keywords'].'"</div>';
    }

    echo $datagrid_result;
}
/* main content end */
?>
