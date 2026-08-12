import { setupAdminBulkTables, setupAdminContentReveal, setupAdminMobileNav, setupAdminScrollHeader } from '../admin/admin-dom';

export function bootShell() {
    setupAdminMobileNav();
    setupAdminScrollHeader();
    setupAdminContentReveal();
    setupAdminBulkTables();
}

export function refreshShell() {
    setupAdminContentReveal();
    setupAdminBulkTables();
}
