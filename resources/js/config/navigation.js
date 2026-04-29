/**
 * Admin Sidebar Navigation Configuration
 *
 * Each item should have:
 * - label: Display text
 * - icon: Icon name (dashboard, users, receipt, currency, chart, settings)
 * - href: Route path
 * - children: (optional) Array of sub-items
 */

export const adminNavItems = [
    {
        label: 'Dashboard',
        icon: 'dashboard',
        href: '/admin/dashboard'
    },
    {
        label: 'Monthly Procedure',
        icon: 'calendar',
        href: '/admin/monthly-procedure'
    },
    {
        label: 'Fee Structure',
        icon: 'chart',
        href: '/admin/fee-structure'
    },
    {
        label: 'Fee Categories',
        icon: 'currency',
        href: '/admin/fee-fund-categories'
    },
    {
        label: 'Fee Fund Heads',
        icon: 'list',
        href: '/admin/fee-fund-heads'
    },
    {
        label: 'Regions',
        icon: 'map',
        href: '/admin/regions'
    },
    {
        label: 'Levels',
        icon: 'stairs',
        href: '/admin/levels'
    },
    {
        label: 'Classes',
        icon: 'list',
        href: '/admin/classes'
    },
    {
        label: 'Year Sessions',
        icon: 'calendar',
        href: '/admin/year-sessions'
    },
    {
        label: 'Institutions',
        icon: 'building',
        href: '/admin/institutions'
    },
    {
        label: 'Consumers',
        icon: 'users',
        href: '#consumers',
        children: [
            { label: '→ Students', href: '/admin/consumers/student' },
        ]
    },
    {
        label: 'Security',
        icon: 'security',
        href: '#security',
        children: [
            { label: '→ Audit Logs', href: '/admin/security-audit' },
        ]
    },
    {
        label: 'Testing Area',
        icon: 'tool',
        href: '#testing',
        children: [
            { label: '→ API Sandbox', href: '/admin/api-testing' },
            { label: '→ 1Link API Test', href: '/admin/one-link-testing' },
        ]
    },
    {
        label: 'Settings',
        icon: 'settings',
        href: '#utilities',
        children: [
            { label: '→ Category Update', href: '/admin/utilities/categoryBind' },
            { label: '→ Challan Update', href: '/admin/utilities/challanUpdate' },
            { label: '→ Challan History', href: '/admin/settings/challan-history' },
        ]
    },
];
