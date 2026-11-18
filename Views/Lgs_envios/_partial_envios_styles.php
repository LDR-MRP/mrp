<style>
/* Modern UI Tokens Scoped to Envíos / Pre-envíos */
:root {
    --brand-primary: #C46623;
    --brand-primary-hover: #b0571c;
    --brand-dark: #0F172A;
    --surface-card: #FFFFFF;
    --surface-border: #E2E8F0;
    --surface-bg: #F8FAFC;
}

.envios-surface-card {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: 18px;
    box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.kpi-card-modern {
    background: #FFFFFF;
    border: 1px solid var(--surface-border);
    border-radius: 16px;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
    position: relative;
    cursor: pointer;
}
.kpi-card-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px -4px rgba(15, 23, 42, 0.08);
}
.kpi-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: transparent;
    transition: background 0.2s ease;
}
.kpi-card-modern.border-top-primary::before { background: var(--brand-primary); }
.kpi-card-modern.border-top-warning::before { background: #F59E0B; }
.kpi-card-modern.border-top-info::before { background: #0284C7; }
.kpi-card-modern.border-top-success::before { background: #10B981; }
.kpi-card-modern.border-top-danger::before { background: #EF4444; }
.kpi-card-modern.border-top-purple::before { background: #8B5CF6; }
.kpi-card-modern.border-top-secondary::before { background: #64748B; }

/* Smart Search Pill */
.search-pill-modern {
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 30px;
    padding: 6px 16px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}
.search-pill-modern:focus-within {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 3px rgba(196, 102, 35, 0.15);
}
.search-pill-modern input {
    border: none;
    outline: none;
    box-shadow: none;
    font-size: 13px;
    background: transparent;
}

/* Quick Filter Chips */
.filter-chip-btn {
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    font-size: 11.5px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    transition: all 0.2s ease;
    cursor: pointer;
    user-select: none;
}
.filter-chip-btn:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
    color: #0F172A;
}
.filter-chip-btn.active {
    background: var(--brand-primary);
    border-color: var(--brand-primary);
    color: #FFFFFF;
    box-shadow: 0 2px 8px rgba(196, 102, 35, 0.25);
}

/* Ocultar input default plano de DataTables */
#tableEnvios_filter {
    display: none;
}
#tableEnvios_wrapper .dataTables_length {
    padding: 12px 16px 6px;
}
#tableEnvios_wrapper .dataTables_length label {
    font-size: 12px;
    color: #64748B;
    font-weight: 500;
}
#tableEnvios_wrapper .dataTables_length select {
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    padding: 4px 8px;
    font-size: 12px;
    margin: 0 4px;
}
#tableEnvios_wrapper .dataTables_info,
#tableEnvios_wrapper .dataTables_paginate {
    padding: 12px 16px;
    font-size: 12px;
}

/* Radio Cards para Modalidad de Cálculo */
.radio-card-option {
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
    padding: 12px 14px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    background: #FFFFFF;
}
.radio-card-option:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
    transform: translateY(-1px);
}
.radio-card-option.active {
    border-color: var(--brand-primary);
    background: #FFF7ED;
    box-shadow: 0 0 0 2px rgba(196, 102, 35, 0.15);
}
.radio-card-option .radio-dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #CBD5E1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.radio-card-option.active .radio-dot {
    border-color: var(--brand-primary);
}
.radio-card-option.active .radio-dot::after {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--brand-primary);
}

/* Nodos del Itinerario Stepper */
.nodo-item {
    border-radius: 14px !important;
    border: 1px solid #E2E8F0 !important;
    background: #FFFFFF !important;
    box-shadow: 0 3px 10px -2px rgba(15, 23, 42, 0.04) !important;
    transition: all 0.2s ease;
}
.nodo-item:hover {
    box-shadow: 0 8px 20px -3px rgba(15, 23, 42, 0.08) !important;
}
.nodo-item .form-select-sm, .nodo-item .form-control-sm {
    border-radius: 8px;
    border-color: #CBD5E1;
}
.nodo-item .form-select-sm:focus, .nodo-item .form-control-sm:focus {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 2px rgba(196, 102, 35, 0.15);
}

/* Unified Toolbar & Table Action Buttons */
.btn-toolbar-lgs {
    height: 36px;
    padding: 0 14px;
    font-size: 12.5px;
    font-weight: 600;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
    transition: all 0.18s ease;
}
.btn-table-action {
    height: 30px;
    padding: 0 11px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    white-space: nowrap;
    line-height: 1;
}
.btn-icon-lgs {
    width: 30px;
    height: 30px;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    line-height: 1;
    flex-shrink: 0;
    transition: all 0.15s ease;
}
.lgs-route-cell {
    max-width: 250px;
}
.lgs-route-line {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#tableEnvios td {
    vertical-align: middle;
    padding-top: 10px;
    padding-bottom: 10px;
}
</style>
