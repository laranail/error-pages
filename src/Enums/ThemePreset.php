<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Enums;

/**
 * A named theme preset: a full set of light/dark design tokens applied to the
 * single error-page layout as `--sep-*` CSS custom properties. Themes (not
 * structural layout variants) are the customization axis — an error page needs
 * one solid layout and a look that matches the brand.
 *
 * Config `theme.colors` overrides merge on top of the chosen preset, so a brand
 * can start from a preset and tweak individual tokens without a rebuild.
 */
enum ThemePreset: string
{
    case Default = 'default';
    case Slate = 'slate';
    case Midnight = 'midnight';
    case Emerald = 'emerald';
    case Crimson = 'crimson';

    public static function fromValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Default;
    }

    /**
     * Light-scheme tokens for this preset.
     *
     * @return array<string, string>
     */
    public function light(): array
    {
        return match ($this) {
            self::Default => self::tokens('#f8fafc', '#ffffff', '#0f172a', '#64748b', '#4f46e5', '#e2e8f0'),
            self::Slate => self::tokens('#f8fafc', '#ffffff', '#0f172a', '#64748b', '#475569', '#e2e8f0'),
            self::Midnight => self::tokens('#eef2ff', '#ffffff', '#1e1b4b', '#6366f1', '#4f46e5', '#e0e7ff'),
            self::Emerald => self::tokens('#f0fdf4', '#ffffff', '#052e16', '#4b5563', '#059669', '#dcfce7'),
            self::Crimson => self::tokens('#fff7f7', '#ffffff', '#450a0a', '#6b7280', '#dc2626', '#fee2e2'),
        };
    }

    /**
     * Dark-scheme tokens for this preset.
     *
     * @return array<string, string>
     */
    public function dark(): array
    {
        return match ($this) {
            self::Default => self::tokens('#0b1120', '#111827', '#f1f5f9', '#94a3b8', '#818cf8', '#1f2937'),
            self::Slate => self::tokens('#0f172a', '#1e293b', '#f1f5f9', '#94a3b8', '#cbd5e1', '#334155'),
            self::Midnight => self::tokens('#020617', '#0f172a', '#e2e8f0', '#64748b', '#6366f1', '#1e293b'),
            self::Emerald => self::tokens('#071a12', '#0f2a1e', '#ecfdf5', '#6b7280', '#34d399', '#14532d'),
            self::Crimson => self::tokens('#180b0b', '#241416', '#fef2f2', '#9ca3af', '#f87171', '#4c1d1d'),
        };
    }

    /**
     * @return array<string, string>
     */
    private static function tokens(string $bg, string $surface, string $text, string $muted, string $accent, string $border): array
    {
        return [
            'bg' => $bg,
            'surface' => $surface,
            'text' => $text,
            'muted' => $muted,
            'accent' => $accent,
            'border' => $border,
        ];
    }
}
