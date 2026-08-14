import './bootstrap';
import { setupAdminTheme } from './admin/useTheme';
import {
    setupAdminContentReveal,
    setupAdminMobileNav,
    setupAdminScrollHeader,
    setupAdminLoginReady,
} from './admin/admin-dom';

document.addEventListener('DOMContentLoaded', () => {
    setupAdminTheme();
    setupAdminMobileNav();
    setupAdminScrollHeader();
    setupAdminContentReveal();
    setupAdminLoginReady();
});
