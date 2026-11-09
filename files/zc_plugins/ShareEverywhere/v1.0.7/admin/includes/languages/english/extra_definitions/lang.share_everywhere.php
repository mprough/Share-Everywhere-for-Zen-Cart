<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit('Illegal access');
}

return ['BOX_CATALOG_SHARE_EVERYWHERE' => 'Share Everywhere'];
