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
            { label: '→ Institutions', href: '/admin/consumers/institution' },
            { label: '→ Inductees', href: '/admin/consumers/inductee' },
        ]
    },
    {
        label: 'APIs',
        icon: 'tool',
        href: '#api',
        children: [
            { label: '→ Fetch Students', href: '/admin/api/fetch/student', action: 'fetch' },
            { label: '→ Fetch Institutions', href: '/admin/api/fetch/institution', action: 'fetch' },
            { label: '→ Fetch Inductees', href: '/admin/api/fetch/inductee', action: 'fetch' },
        ]
    },
    {
        label: 'Settings',
        icon: 'settings',
        href: '#utilities',
        children: [
            { label: '→ Category Update', href: '/admin/utilities/categoryBind' },
            { label: '→ Challan Update', href: '/admin/utilities/challanUpdate' },
            { label: '→ Generate Challans', href: '/admin/utilities/generateChallans', action: 'generate' },
            { label: '→ 1Link API Test', href: '/admin/one-link-testing' },
        ]
    },
];
