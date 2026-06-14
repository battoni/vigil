import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

// Button severity mappings are token-identical across schemes — the referenced
// tokens ({primary.color}, {surface.100}, …) flip per color scheme, so the same
// object drives both light and dark.
const BUTTON_ROOT = {
  primary: {
    background: '{primary.color}',
    hoverBackground: '{primary.hover.color}',
    activeBackground: '{primary.active.color}',
    borderColor: '{primary.color}',
    hoverBorderColor: '{primary.hover.color}',
    activeBorderColor: '{primary.active.color}',
    color: '{primary.contrast.color}',
    hoverColor: '{primary.contrast.color}',
    activeColor: '{primary.contrast.color}',
    focusRing: {
      color: '{primary.color}',
      shadow: 'none',
    },
  },
  secondary: {
    background: '{surface.100}',
    hoverBackground: '{surface.200}',
    activeBackground: '{surface.300}',
    borderColor: '{surface.100}',
    hoverBorderColor: '{surface.200}',
    activeBorderColor: '{surface.300}',
    color: '{surface.600}',
    hoverColor: '{surface.700}',
    activeColor: '{surface.800}',
    focusRing: {
      color: '{surface.600}',
      shadow: 'none',
    },
  },
  success: {
    background: '{success.color}',
    hoverBackground: '{success.hover.color}',
    activeBackground: '{success.active.color}',
    borderColor: '{success.color}',
    hoverBorderColor: '{success.hover.color}',
    activeBorderColor: '{success.active.color}',
    color: '{success.contrast.color}',
    hoverColor: '{success.contrast.color}',
    activeColor: '{success.contrast.color}',
    focusRing: {
      color: '{success.color}',
      shadow: 'none',
    },
  },
  info: {
    background: '{info.color}',
    hoverBackground: '{info.hover.color}',
    activeBackground: '{info.active.color}',
    borderColor: '{info.color}',
    hoverBorderColor: '{info.hover.color}',
    activeBorderColor: '{info.active.color}',
    color: '{info.contrast.color}',
    hoverColor: '{info.contrast.color}',
    activeColor: '{info.contrast.color}',
    focusRing: {
      color: '{info.color}',
      shadow: 'none',
    },
  },
  warn: {
    background: '{warn.color}',
    hoverBackground: '{warn.hover.color}',
    activeBackground: '{warn.active.color}',
    borderColor: '{warn.color}',
    hoverBorderColor: '{warn.hover.color}',
    activeBorderColor: '{warn.active.color}',
    color: '{warn.contrast.color}',
    hoverColor: '{warn.contrast.color}',
    activeColor: '{warn.contrast.color}',
    focusRing: {
      color: '{warn.color}',
      shadow: 'none',
    },
  },
  danger: {
    background: '{danger.color}',
    hoverBackground: '{danger.hover.color}',
    activeBackground: '{danger.active.color}',
    borderColor: '{danger.color}',
    hoverBorderColor: '{danger.hover.color}',
    activeBorderColor: '{danger.active.color}',
    color: '{danger.contrast.color}',
    hoverColor: '{danger.contrast.color}',
    activeColor: '{danger.contrast.color}',
    focusRing: {
      color: '{danger.color}',
      shadow: 'none',
    },
  },
  help: {
    background: '{help.500}',
    hoverBackground: '{help.600}',
    activeBackground: '{help.700}',
    borderColor: '{help.500}',
    hoverBorderColor: '{help.600}',
    activeBorderColor: '{help.700}',
    color: '#ffffff',
    hoverColor: '#ffffff',
    activeColor: '#ffffff',
    focusRing: {
      color: '{help.500}',
      shadow: 'none',
    },
  },
  contrast: {
    background: '{contrast.500}',
    hoverBackground: '{contrast.600}',
    activeBackground: '{contrast.700}',
    borderColor: '{contrast.500}',
    hoverBorderColor: '{contrast.600}',
    activeBorderColor: '{contrast.700}',
    color: '#ffffff',
    hoverColor: '#ffffff',
    activeColor: '#ffffff',
    focusRing: {
      color: '{contrast.500}',
      shadow: 'none',
    },
  },
};

// Only `secondary` reads neutral surface shades that mean different things per
// scheme; in dark it needs the dark end of the (non-inverted) surface scale.
const BUTTON_ROOT_DARK = {
  ...BUTTON_ROOT,
  secondary: {
    background: '{surface.800}',
    hoverBackground: '{surface.700}',
    activeBackground: '{surface.600}',
    borderColor: '{surface.800}',
    hoverBorderColor: '{surface.700}',
    activeBorderColor: '{surface.600}',
    color: '{surface.0}',
    hoverColor: '{surface.0}',
    activeColor: '{surface.0}',
    focusRing: {
      color: '{surface.300}',
      shadow: 'none',
    },
  },
};

export default definePreset(Aura, {
  primitive: {
    // Radius reads from the active theme's CSS vars (the same ones Tailwind's
    // rounded-* utilities use), with fallbacks to the app.vigil defaults. A theme
    // file can reshape every component's geometry by overriding --radius-*.
    borderRadius: {
      none: '0',
      xs: 'var(--radius-xs, 2px)',
      sm: 'var(--radius-sm, 4px)',
      md: 'var(--radius-md, 6px)',
      lg: 'var(--radius-lg, 8px)',
      xl: 'var(--radius-xl, 10px)',
    },

    primary: {
      50: 'var(--color-primary-50)',
      100: 'var(--color-primary-100)',
      200: 'var(--color-primary-200)',
      300: 'var(--color-primary-300)',
      400: 'var(--color-primary-400)',
      500: 'var(--color-primary-500)',
      600: 'var(--color-primary-600)',
      700: 'var(--color-primary-700)',
      800: 'var(--color-primary-800)',
      900: 'var(--color-primary-900)',
      950: 'var(--color-primary-950)',
    },
    ink: {
      50: 'var(--color-ink-50)',
      100: 'var(--color-ink-100)',
      200: 'var(--color-ink-200)',
      300: 'var(--color-ink-300)',
      400: 'var(--color-ink-400)',
      500: 'var(--color-ink-500)',
      600: 'var(--color-ink-600)',
      700: 'var(--color-ink-700)',
      800: 'var(--color-ink-800)',
      900: 'var(--color-ink-900)',
      950: 'var(--color-ink-950)',
    },
    surface: {
      50: 'var(--color-surface-50)',
      100: 'var(--color-surface-100)',
      200: 'var(--color-surface-200)',
      300: 'var(--color-surface-300)',
      400: 'var(--color-surface-400)',
      500: 'var(--color-surface-500)',
      600: 'var(--color-surface-600)',
      700: 'var(--color-surface-700)',
      800: 'var(--color-surface-800)',
      900: 'var(--color-surface-900)',
      950: 'var(--color-surface-950)',
    },
    success: {
      50: 'var(--color-success-50)',
      100: 'var(--color-success-100)',
      200: 'var(--color-success-200)',
      300: 'var(--color-success-300)',
      400: 'var(--color-success-400)',
      500: 'var(--color-success-500)',
      600: 'var(--color-success-600)',
      700: 'var(--color-success-700)',
      800: 'var(--color-success-800)',
      900: 'var(--color-success-900)',
      950: 'var(--color-success-950)',
    },
    info: {
      50: 'var(--color-info-50)',
      100: 'var(--color-info-100)',
      200: 'var(--color-info-200)',
      300: 'var(--color-info-300)',
      400: 'var(--color-info-400)',
      500: 'var(--color-info-500)',
      600: 'var(--color-info-600)',
      700: 'var(--color-info-700)',
      800: 'var(--color-info-800)',
      900: 'var(--color-info-900)',
      950: 'var(--color-info-950)',
    },
    warn: {
      50: 'var(--color-warn-50)',
      100: 'var(--color-warn-100)',
      200: 'var(--color-warn-200)',
      300: 'var(--color-warn-300)',
      400: 'var(--color-warn-400)',
      500: 'var(--color-warn-500)',
      600: 'var(--color-warn-600)',
      700: 'var(--color-warn-700)',
      800: 'var(--color-warn-800)',
      900: 'var(--color-warn-900)',
      950: 'var(--color-warn-950)',
    },
    danger: {
      50: 'var(--color-danger-50)',
      100: 'var(--color-danger-100)',
      200: 'var(--color-danger-200)',
      300: 'var(--color-danger-300)',
      400: 'var(--color-danger-400)',
      500: 'var(--color-danger-500)',
      600: 'var(--color-danger-600)',
      700: 'var(--color-danger-700)',
      800: 'var(--color-danger-800)',
      900: 'var(--color-danger-900)',
      950: 'var(--color-danger-950)',
    },
    help: {
      50: 'var(--color-help-50)',
      100: 'var(--color-help-100)',
      200: 'var(--color-help-200)',
      300: 'var(--color-help-300)',
      400: 'var(--color-help-400)',
      500: 'var(--color-help-500)',
      600: 'var(--color-help-600)',
      700: 'var(--color-help-700)',
      800: 'var(--color-help-800)',
      900: 'var(--color-help-900)',
      950: 'var(--color-help-950)',
    },
    contrast: {
      50: 'var(--color-contrast-50)',
      100: 'var(--color-contrast-100)',
      200: 'var(--color-contrast-200)',
      300: 'var(--color-contrast-300)',
      400: 'var(--color-contrast-400)',
      500: 'var(--color-contrast-500)',
      600: 'var(--color-contrast-600)',
      700: 'var(--color-contrast-700)',
      800: 'var(--color-contrast-800)',
      900: 'var(--color-contrast-900)',
      950: 'var(--color-contrast-950)',
    },
  },

  semantic: {
    fontFamily: {
      base: "'Space Grotesk', ui-sans-serif, system-ui, sans-serif",
      heading: "'Space Grotesk', ui-sans-serif, system-ui, sans-serif",
      body: "'Sometype Mono', ui-monospace, monospace",
      mono: "'Sometype Mono', ui-monospace, monospace",
    },

    // Mirror `primitive` scales here with raw `var(--color-*)` (not `{primary.500}`) so we override
    // Aura’s emerald/semantic defaults without a self-referential token that collides on `primary.500`.

    primary: {
      50: 'var(--color-primary-50)',
      100: 'var(--color-primary-100)',
      200: 'var(--color-primary-200)',
      300: 'var(--color-primary-300)',
      400: 'var(--color-primary-400)',
      500: 'var(--color-primary-500)',
      600: 'var(--color-primary-600)',
      700: 'var(--color-primary-700)',
      800: 'var(--color-primary-800)',
      900: 'var(--color-primary-900)',
      950: 'var(--color-primary-950)',
    },

    colorScheme: {
      light: {
        surface: {
          0: '#ffffff',
          50: 'var(--color-surface-50)',
          100: 'var(--color-surface-100)',
          200: 'var(--color-surface-200)',
          300: 'var(--color-surface-300)',
          400: 'var(--color-surface-400)',
          500: 'var(--color-surface-500)',
          600: 'var(--color-surface-600)',
          700: 'var(--color-surface-700)',
          800: 'var(--color-surface-800)',
          900: 'var(--color-surface-900)',
          950: 'var(--color-surface-950)',
        },

        primary: {
          color: '{primary.500}',
          contrastColor: '{ink.950}',
          contrast: {
            color: '{ink.950}',
          },
          hover: {
            color: '{primary.600}',
          },
          active: {
            color: '{primary.700}',
          },
          hoverColor: '{primary.600}',
          activeColor: '{primary.700}',
        },

        focusRing: {
          color: '{primary.500}',
          width: '1px',
          style: 'solid',
          offset: '2px',
        },

        text: {
          color: '{surface.950}',
          hoverColor: '{surface.950}',
          mutedColor: '{surface.600}',
          highlightBackground: '{primary.50}',
          highlightColor: '{primary.700}',
        },

        formField: {
          paddingX: '0.75rem',
          paddingY: '0.5rem',
          borderRadius: '{border.radius.md}',
          focusRing: {
            width: '1px',
            style: 'solid',
            color: '{primary.500}',
            offset: '0',
          },
        },

        content: {
          borderRadius: '{border.radius.md}',
        },

        overlay: {
          select: {
            borderRadius: '{border.radius.md}',
          },
          popover: {
            borderRadius: '{border.radius.md}',
          },
        },

        success: {
          color: '{success.500}',
          contrastColor: '#ffffff',
          contrast: {
            color: '#ffffff',
          },
          hover: {
            color: '{success.600}',
          },
          active: {
            color: '{success.700}',
          },
          hoverColor: '{success.600}',
          activeColor: '{success.700}',
        },
        info: {
          color: '{info.500}',
          contrastColor: '#ffffff',
          contrast: {
            color: '#ffffff',
          },
          hover: {
            color: '{info.600}',
          },
          active: {
            color: '{info.700}',
          },
          hoverColor: '{info.600}',
          activeColor: '{info.700}',
        },
        warn: {
          color: '{warn.500}',
          contrastColor: '#ffffff',
          contrast: {
            color: '#ffffff',
          },
          hover: {
            color: '{warn.600}',
          },
          active: {
            color: '{warn.700}',
          },
          hoverColor: '{warn.600}',
          activeColor: '{warn.700}',
        },
        danger: {
          color: '{danger.500}',
          contrastColor: '#ffffff',
          contrast: {
            color: '#ffffff',
          },
          hover: {
            color: '{danger.600}',
          },
          active: {
            color: '{danger.700}',
          },
          hoverColor: '{danger.600}',
          activeColor: '{danger.700}',
        },
      },
      dark: {
        // Standard orientation (0 = lightest, 950 = darkest), brand-warm values.
        // Aura's dark tokens reference high indices for backgrounds
        // (content.background={surface.900}, formField.background={surface.950})
        // and low indices for text ({surface.0}), so the scale must NOT be inverted.
        surface: {
          0: '#ffffff',
          50: '#f3f4f3',
          100: '#dde2e6',
          200: '#d1d6d9',
          300: '#c5c9cc',
          400: '#b8bdbf',
          500: '#888b8d',
          600: '#7b7e80',
          700: '#6f7173',
          800: '#565859',
          900: '#252626', // cards / panels (content.background)
          950: '#141413', // deepest / inputs (formField.background) — brand signature dark
        },

        primary: {
          color: '{primary.500}',
          contrastColor: '{ink.950}',
          contrast: {
            color: '{ink.950}',
          },
          hover: {
            color: '{primary.400}',
          },
          active: {
            color: '{primary.300}',
          },
          hoverColor: '{primary.400}',
          activeColor: '{primary.300}',
        },

        focusRing: {
          color: '{primary.500}',
          width: '1px',
          style: 'solid',
          offset: '2px',
        },

        text: {
          color: '{surface.0}',
          hoverColor: '{surface.0}',
          mutedColor: '{surface.400}',
          highlightBackground: '{surface.800}',
          highlightColor: '{primary.400}',
        },

        formField: {
          // Inputs are raised (#252626) so they read on the body-toned dialog (#141413).
          background: '{surface.900}',
          borderColor: '{surface.700}',
          color: '{surface.0}',
          paddingX: '0.75rem',
          paddingY: '0.5rem',
          borderRadius: '{border.radius.md}',
          focusRing: {
            width: '1px',
            style: 'solid',
            color: '{primary.500}',
            offset: '0',
          },
        },

        content: {
          borderRadius: '{border.radius.md}',
        },

        overlay: {
          select: {
            borderRadius: '{border.radius.md}',
          },
          popover: {
            borderRadius: '{border.radius.md}',
          },
        },

        success: {
          color: '{success.500}',
          contrastColor: '#ffffff',
          contrast: {
            color: '#ffffff',
          },
          hover: {
            color: '{success.400}',
          },
          active: {
            color: '{success.300}',
          },
          hoverColor: '{success.400}',
          activeColor: '{success.300}',
        },
        info: {
          color: '{info.500}',
          contrastColor: '#ffffff',
          contrast: {
            color: '#ffffff',
          },
          hover: {
            color: '{info.400}',
          },
          active: {
            color: '{info.300}',
          },
          hoverColor: '{info.400}',
          activeColor: '{info.300}',
        },
        warn: {
          color: '{warn.500}',
          contrastColor: '{ink.950}',
          contrast: {
            color: '{ink.950}',
          },
          hover: {
            color: '{warn.400}',
          },
          active: {
            color: '{warn.300}',
          },
          hoverColor: '{warn.400}',
          activeColor: '{warn.300}',
        },
        danger: {
          color: '{danger.500}',
          contrastColor: '#ffffff',
          contrast: {
            color: '#ffffff',
          },
          hover: {
            color: '{danger.400}',
          },
          active: {
            color: '{danger.300}',
          },
          hoverColor: '{danger.400}',
          activeColor: '{danger.300}',
        },
      },
    },

    success: {
      50: 'var(--color-success-50)',
      100: 'var(--color-success-100)',
      200: 'var(--color-success-200)',
      300: 'var(--color-success-300)',
      400: 'var(--color-success-400)',
      500: 'var(--color-success-500)',
      600: 'var(--color-success-600)',
      700: 'var(--color-success-700)',
      800: 'var(--color-success-800)',
      900: 'var(--color-success-900)',
      950: 'var(--color-success-950)',
    },
    info: {
      50: 'var(--color-info-50)',
      100: 'var(--color-info-100)',
      200: 'var(--color-info-200)',
      300: 'var(--color-info-300)',
      400: 'var(--color-info-400)',
      500: 'var(--color-info-500)',
      600: 'var(--color-info-600)',
      700: 'var(--color-info-700)',
      800: 'var(--color-info-800)',
      900: 'var(--color-info-900)',
      950: 'var(--color-info-950)',
    },
    warn: {
      50: 'var(--color-warn-50)',
      100: 'var(--color-warn-100)',
      200: 'var(--color-warn-200)',
      300: 'var(--color-warn-300)',
      400: 'var(--color-warn-400)',
      500: 'var(--color-warn-500)',
      600: 'var(--color-warn-600)',
      700: 'var(--color-warn-700)',
      800: 'var(--color-warn-800)',
      900: 'var(--color-warn-900)',
      950: 'var(--color-warn-950)',
    },
    danger: {
      50: 'var(--color-danger-50)',
      100: 'var(--color-danger-100)',
      200: 'var(--color-danger-200)',
      300: 'var(--color-danger-300)',
      400: 'var(--color-danger-400)',
      500: 'var(--color-danger-500)',
      600: 'var(--color-danger-600)',
      700: 'var(--color-danger-700)',
      800: 'var(--color-danger-800)',
      900: 'var(--color-danger-900)',
      950: 'var(--color-danger-950)',
    },
    help: {
      50: 'var(--color-help-50)',
      100: 'var(--color-help-100)',
      200: 'var(--color-help-200)',
      300: 'var(--color-help-300)',
      400: 'var(--color-help-400)',
      500: 'var(--color-help-500)',
      600: 'var(--color-help-600)',
      700: 'var(--color-help-700)',
      800: 'var(--color-help-800)',
      900: 'var(--color-help-900)',
      950: 'var(--color-help-950)',
    },
    contrast: {
      50: 'var(--color-contrast-50)',
      100: 'var(--color-contrast-100)',
      200: 'var(--color-contrast-200)',
      300: 'var(--color-contrast-300)',
      400: 'var(--color-contrast-400)',
      500: 'var(--color-contrast-500)',
      600: 'var(--color-contrast-600)',
      700: 'var(--color-contrast-700)',
      800: 'var(--color-contrast-800)',
      900: 'var(--color-contrast-900)',
      950: 'var(--color-contrast-950)',
    },

    datatable: {
      border: {
        color: '{surface.200}',
      },
    },
  },

  components: {
    global: {
      css: `
        :root {
          --font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
        }

        * {
          font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
        }

        p, .p-text, .p-paragraph {
          font-family: 'Sometype Mono', ui-monospace, monospace;
        }

        code, pre, .p-code {
          font-family: 'Sometype Mono', ui-monospace, monospace;
        }

        h1, h2, h3, h4, h5, h6,
        button, .p-button,
        label, .p-label,
        li, span,
        .p-card-title,
        .p-panel-title,
        .p-dialog-title,
        .p-menuitem-text {
          font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
        }
      `,
    },

    button: {
      root: {
        borderRadius: '{border.radius.md}',
        roundedBorderRadius: '{border.radius.lg}',
      },
      colorScheme: {
        light: { root: BUTTON_ROOT } as any,
        dark: { root: BUTTON_ROOT_DARK } as any,
      },
    },

    card: {
      root: {
        borderRadius: '{border.radius.lg}',
      },
    },

    message: {
      colorScheme: {
        light: {
          success: {
            background: '{success.50}',
            borderColor: '{success.200}',
            color: '{success.700}',
            shadow: '0px 4px 8px 0px rgba(34, 197, 94, 0.04)',
          },
          info: {
            background: '{info.50}',
            borderColor: '{info.200}',
            color: '{info.700}',
            shadow: '0px 4px 8px 0px rgba(59, 130, 246, 0.04)',
          },
          warn: {
            background: '{warn.50}',
            borderColor: '{warn.200}',
            color: '{warn.700}',
            shadow: '0px 4px 8px 0px rgba(245, 158, 11, 0.04)',
          },
          error: {
            background: '{danger.50}',
            borderColor: '{danger.200}',
            color: '{danger.700}',
            shadow: '0px 4px 8px 0px rgba(239, 68, 68, 0.04)',
          },
        },
        dark: {
          success: {
            background: '{success.950}',
            borderColor: '{success.800}',
            color: '{success.300}',
          },
          info: {
            background: '{info.950}',
            borderColor: '{info.800}',
            color: '{info.300}',
          },
          warn: {
            background: '{warn.950}',
            borderColor: '{warn.800}',
            color: '{warn.300}',
          },
          error: {
            background: '{danger.950}',
            borderColor: '{danger.800}',
            color: '{danger.300}',
          },
        },
      },
    },

    tag: {
      colorScheme: {
        light: {
          root: {
            background: '{primary.50}',
            color: 'var(--color-ink-900)',
          },
          success: {
            background: '{success.100}',
            color: '{success.700}',
          },
          info: {
            background: '{info.100}',
            color: '{info.700}',
          },
          warn: {
            background: '{warn.100}',
            color: '{warn.700}',
          },
          danger: {
            background: '{danger.100}',
            color: '{danger.700}',
          },
        } as any,
        dark: {
          root: {
            background: '{surface.100}',
            color: '{primary.400}',
          },
          success: {
            background: '{success.950}',
            color: '{success.300}',
          },
          info: {
            background: '{info.950}',
            color: '{info.300}',
          },
          warn: {
            background: '{warn.950}',
            color: '{warn.300}',
          },
          danger: {
            background: '{danger.950}',
            color: '{danger.300}',
          },
        } as any,
      },
    },

    badge: {
      colorScheme: {
        light: {
          root: {
            background: '{surface.900}',
            color: '{surface.0}',
          },
          secondary: {
            background: '{surface.200}',
            color: '{surface.700}',
          },
          success: {
            background: '{success.500}',
            color: '#ffffff',
          },
          info: {
            background: '{info.500}',
            color: '#ffffff',
          },
          warn: {
            background: '{warn.500}',
            color: '#ffffff',
          },
          danger: {
            background: '{danger.500}',
            color: '#ffffff',
          },
          contrast: {
            background: '{surface.900}',
            color: '{surface.0}',
          },
        } as any,
        // Badge severities reference surface/semantic tokens that resolve per
        // scheme, so the dark mapping mirrors light.
        dark: {
          root: {
            background: '{surface.900}',
            color: '{surface.0}',
          },
          secondary: {
            background: '{surface.200}',
            color: '{surface.700}',
          },
          success: {
            background: '{success.500}',
            color: '#ffffff',
          },
          info: {
            background: '{info.500}',
            color: '#ffffff',
          },
          warn: {
            background: '{warn.500}',
            color: '#ffffff',
          },
          danger: {
            background: '{danger.500}',
            color: '#ffffff',
          },
          contrast: {
            background: '{surface.900}',
            color: '{surface.0}',
          },
        } as any,
      },
    },

    tabs: {
      colorScheme: {
        light: {
          tab: {
            active: {
              color: '{primary.700}',
            },
          },
        } as any,
        dark: {
          tab: {
            active: {
              color: '{primary.400}',
            },
          },
        } as any,
      },
    },

    divider: {
      colorScheme: {
        light: {
          root: {
            borderColor: '{primary.color}',
          },
        },
        dark: {
          root: {
            borderColor: '{primary.color}',
          },
        },
      },
    },

    // In dark, the dialog takes the body tone (#141413) so it reads as part of the
    // page (matching light, where dialog == body white) — raised inputs (#252626)
    // provide the contrast. This also removes the footer seam.
    // The dialog reads the body tone (--color-canvas) in every theme + scheme, so
    // it blends with the page instead of floating as a lighter box. Raised inputs
    // provide the contrast. A border keeps it delineated where bg == body exactly.
    dialog: {
      colorScheme: {
        light: {
          root: {
            background: 'var(--color-canvas)',
            borderColor: 'var(--color-line-strong)',
          },
        },
        dark: {
          root: {
            background: 'var(--color-canvas)',
            borderColor: 'var(--color-line-strong)',
          },
        },
      },
    },
  },
});
