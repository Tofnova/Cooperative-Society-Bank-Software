<?php
// Header include korar PORE ei file include korun — super_admin chara onno keu dhukte parbe na
if (!is_super()) {
    deny_access();
}
