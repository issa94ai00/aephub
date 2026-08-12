export function localizedName(item, locale) {
    if (!item) {
        return '';
    }
    return locale === 'en' && item.name_en ? item.name_en : item.name;
}

export function fmt(n, digits = 0) {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: digits }).format(Number(n) || 0);
}

export function fmtMoney(n) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(n) || 0);
}

export function fmtDateTime(iso) {
    if (!iso) {
        return '—';
    }
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        return iso;
    }
    const p = (x) => String(x).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
}

export function unitLabel(unit, t) {
    const tr = t?.units ?? {};
    return tr[unit] || unit || '';
}

export function typeBadgeClass(type) {
    const map = {
        in: 'admin-badge--green',
        out: 'admin-badge--rose',
        adjust: 'admin-badge--violet',
        transfer_in: 'admin-badge--sky',
        transfer_out: 'admin-badge--amber',
    };
    return map[type] || 'admin-badge--neutral';
}
