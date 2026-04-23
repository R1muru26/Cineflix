# Cineflix Food Design System Guidelines

## Overview
The Cineflix Food Design System is a unified collection of design tokens and modular components built to ensure a consistent user experience across all food-related interfaces, including the dedicated Food Menu, Booking Addons, and Chatbot.

## Design Tokens

### Spacing (8px Base Unit)
| Token | Value | Usage |
|-------|-------|-------|
| `--fs-spacing-xs` | 4px | Tight element grouping |
| `--fs-spacing-sm` | 8px | Standard internal padding |
| `--fs-spacing-md` | 16px | Section gaps, card padding |
| `--fs-spacing-lg` | 24px | Major layout gaps |
| `--fs-spacing-xl` | 32px | Container margins |

### Typography
- **Font Family**: `Poppins`, sans-serif
- **Scale**:
  - `h1`: Fluid (clamp 30px to 36px)
  - `xl`: 20px (Section Titles)
  - `lg`: 18px (Card Titles)
  - `base`: 16px (Main Body)
  - `sm`: 14px (Descriptions)
  - `xs`: 12px (Badges/Muted text)

### Colors
- **Primary**: `#c79f5e` (Cineflix Gold)
- **Background**: `#13141a` (Deep Navy/Black)
- **Card BG**: `rgba(30, 30, 30, 0.95)`
- **Success**: `#22c55e`
- **Error**: `#ef4444`

## Component Library

### 1. Food Card (`.fs-food-card`)
- **Aspect Ratio**: 1:1 (Thumbnail)
- **Interaction**: 200ms transform (lift) and shadow transition.
- **Content**: Title, truncated description (2 lines), price, and optional savings badge.

### 2. Responsive Grid (`.fs-grid`)
- **Mobile (<768px)**: 1 column, 16px gap.
- **Tablet (768px-1024px)**: 2 columns, 24px gap.
- **Desktop (>1024px)**: 3-4 columns (auto-fill, min 280px).

### 3. Food Detail Page
- **Hero Image**: 16:9 aspect ratio.
- **Layout**: 2-column grid on desktop, stacked on mobile.
- **Sections**: Header (Title/Desc), Nutritional Info (Grid), Reviews (List), Action Footer.

### 4. Interactive Elements
- **Buttons**: Rounded corners (8px), gradient background, hover lift effect.
- **Inputs**: Full-rounded (pill) for chat/search, 8px for forms.
- **Transitions**: Global 200ms for all hover/active states.

## Chatbot Integration
The chatbot widget follows the design system by using the same:
- Design tokens for padding and margins.
- Typography scale for messages.
- Color palette for user vs. bot bubbles.
- 200ms ease-in-out transitions for panel visibility.

## Accessibility (WCAG 2.1 AA)
- **Contrast**: Minimum 4.5:1 ratio for all text elements.
- **Focus States**: 2px primary color outline for all keyboard-focusable elements.
- **Semantics**: Use of `aria-haspopup`, `role="dialog"`, and `loading="lazy"` for images.
