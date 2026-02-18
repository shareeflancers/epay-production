import { createContext, useContext, useState, useEffect, useMemo } from 'react';
import {
    generateGradientFromColor,
    presetColors,
    presetColorNames,
    baseUI,
    createMantineTheme
} from './themes';

const ThemeContext = createContext(null);

const COLOR_STORAGE_KEY = 'app-primary-color';
const DEFAULT_COLOR = '#7c3aed'; // Violet

export function ThemeProvider({ children, defaultColor = DEFAULT_COLOR }) {
    const [primaryColor, setPrimaryColor] = useState(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem(COLOR_STORAGE_KEY);
            if (saved && /^#[0-9A-Fa-f]{6}$/.test(saved)) {
                return saved;
            }
        }
        return defaultColor;
    });

    // Persist color to localStorage
    useEffect(() => {
        localStorage.setItem(COLOR_STORAGE_KEY, primaryColor);
    }, [primaryColor]);

    // Generate all color variants from the primary color
    const colorConfig = useMemo(() => {
        return generateGradientFromColor(primaryColor);
    }, [primaryColor]);

    // Gradient shorthand
    const gradient = useMemo(() => ({
        from: colorConfig.gradientFrom,
        to: colorConfig.gradientTo,
    }), [colorConfig]);

    // Mantine theme (doesn't include primaryColor to avoid validation error)
    const mantineTheme = useMemo(() => createMantineTheme(), []);

    // Set color from hex
    const setColor = (hexColor) => {
        if (/^#[0-9A-Fa-f]{6}$/.test(hexColor)) {
            setPrimaryColor(hexColor);
        }
    };

    // Set color from preset name
    const setPresetColor = (presetName) => {
        const preset = presetColors.find(
            c => c.name.toLowerCase() === presetName.toLowerCase()
        );
        if (preset) {
            setPrimaryColor(preset.hex);
        }
    };

    const value = {
        // Current primary color (hex)
        primaryColor,

        // Generated color variants
        colorConfig,

        // Gradient shorthand ({ from, to })
        gradient,

        // UI colors (light mode, static)
        ui: baseUI,

        // Preset colors array
        presetColors,
        presetColorNames,

        // Mantine theme object
        theme: mantineTheme,

        // Actions
        setColor,
        setPresetColor,
    };

    return (
        <ThemeContext.Provider value={value}>
            {children}
        </ThemeContext.Provider>
    );
}

export function useTheme() {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return context;
}

// Re-export for convenience
export { presetColors, presetColorNames, generateGradientFromColor, baseUI };
