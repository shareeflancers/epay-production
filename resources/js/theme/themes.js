/**
 * Theme Color System
 *
 * Provides color theming with:
 * - Preset color options
 * - Custom color picker support
 * - Automatic gradient generation from any color
 * - Light mode only (no dark mode)
 */

// Utility to generate gradient colors from a base color
export const generateGradientFromColor = (hexColor) => {
    // Convert hex to HSL for manipulation
    const hex = hexColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16) / 255;
    const g = parseInt(hex.substr(2, 2), 16) / 255;
    const b = parseInt(hex.substr(4, 2), 16) / 255;

    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;

    if (max === min) {
        h = s = 0;
    } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
            case g: h = ((b - r) / d + 2) / 6; break;
            case b: h = ((r - g) / d + 4) / 6; break;
        }
    }

    // Convert HSL to hex
    const hslToHex = (h, s, l) => {
        let r, g, b;
        if (s === 0) {
            r = g = b = l;
        } else {
            const hue2rgb = (p, q, t) => {
                if (t < 0) t += 1;
                if (t > 1) t -= 1;
                if (t < 1/6) return p + (q - p) * 6 * t;
                if (t < 1/2) return q;
                if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                return p;
            };
            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;
            r = hue2rgb(p, q, h + 1/3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1/3);
        }
        const toHex = x => {
            const hex = Math.round(x * 255).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        };
        return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
    };

    // Generate gradient: slightly shift hue for "to" color
    const gradientTo = hslToHex((h + 0.08) % 1, Math.min(s * 1.1, 1), Math.max(l * 0.85, 0.2));

    // Generate hover color (darker version)
    const hover = hslToHex(h, s, Math.max(l * 0.8, 0.15));

    // Generate light color (for backgrounds) - very light, subtle tint
    const light = hslToHex(h, Math.min(s * 0.25, 0.25), Math.min(l * 0.3 + 0.68, 0.96));

    return {
        primary: hexColor,
        gradientFrom: hexColor,
        gradientTo: gradientTo,
        hover: hover,
        light: light,
    };
};

// Preset color options with names
export const presetColors = [
    { name: 'Violet', hex: '#7c3aed' },
    { name: 'Blue', hex: '#2563eb' },
    { name: 'Cyan', hex: '#0891b2' },
    { name: 'Green', hex: '#16a34a' },
    { name: 'Orange', hex: '#ea580c' },
    { name: 'Pink', hex: '#db2777' },
    { name: 'Red', hex: '#dc2626' },
    { name: 'Amber', hex: '#d97706' },
];

// Get color names for quick access
export const presetColorNames = presetColors.map(c => c.name.toLowerCase());

// Base UI colors (light mode only, never changes)
export const baseUI = {
    background: '#f8f9fa',
    surface: '#ffffff',
    surfaceHover: '#f1f3f5',
    border: 'rgba(0, 0, 0, 0.1)',
    text: '#1a1b1e',
    textMuted: '#868e96',
    cardBg: 'rgba(255, 255, 255, 0.95)',
    sidebarBg: 'rgba(255, 255, 255, 0.95)',
    headerBg: 'rgba(255, 255, 255, 0.95)',
};

// Create Mantine theme override (without primaryColor to avoid validation error)
export const createMantineTheme = () => ({
    colorScheme: 'light',
    fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
    headings: {
        fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
    },
});
