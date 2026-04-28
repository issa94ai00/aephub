import './bootstrap';
import {
    setupAdminContentReveal,
    setupAdminMobileNav,
    setupAdminScrollHeader,
    setupAdminLoginReady,
} from './admin/admin-dom';

document.addEventListener('DOMContentLoaded', () => {
    setupAdminMobileNav();
    setupAdminScrollHeader();
    setupAdminContentReveal();
    setupAdminLoginReady();
});
