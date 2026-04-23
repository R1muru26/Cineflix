# Cineflix Design Rollback & Preservation Procedure

## 1. Original Specification Baseline
The following dimensions are the "Golden Standard" for the Cineflix homepage and must be maintained to ensure visual consistency:

### A. Grid Layout
- **Columns**: Fixed `260px` width.
- **Desktop (5-column)**: `repeat(5, 260px)` within a `1600px` max-width container.
- **Tablet (4-column)**: `repeat(4, 260px)` at `< 1700px`.
- **Tablet (3-column)**: `repeat(3, 260px)` at `< 1300px`.
- **Tablet (2-column)**: `repeat(2, 260px)` at `< 1000px`.
- **Mobile (1-column)**: `1fr` at `< 700px` with a card `max-width` of `420px`.

### B. Header & Navigation
- **Logo Height**: Fixed `80px` (desktop).
- **Navigation Gap**: Fixed `2rem` (32px).
- **Search Bar Width**: Fixed `280px`.
- **Avatar Size**: Fixed `45px`.

### C. Spotlight Section
- **Spotlight Height**: Fixed `800px`.
- **Spotlight Content Left**: Fixed `100px`.
- **Spotlight Title (H1)**: Fixed `4.5rem`.
- **Spotlight Text (P)**: Fixed `1.1rem`.

## 2. Recent Alteration Analysis
The layout was recently modified to use "Fluid Auto-Layout" (CSS `clamp()` and `minmax(..., 1fr)`). While this offered better scaling on non-standard viewports, it altered the intended visual density and card proportions on standard desktop displays.

**Identified Changes:**
- Switched to `auto-fill` which caused cards to shrink below the 260px threshold.
- Increased max-width to `1800px`, causing elements to drift further apart.
- Used `clamp()` for logo and avatar, making them smaller than intended on some laptops.

## 3. Preservation & Rollback Procedure
To prevent unauthorized or accidental modifications:
1. **Dimension Locking**: All primary layout values must use fixed `px` or `rem` units rather than fluid `vw`/`vh` or `clamp()` unless explicitly approved for mobile-only viewports.
2. **Breakpoint Strictness**: Do not remove the explicit media query overrides for `1700px`, `1300px`, `1000px`, and `700px`.
3. **Audit Requirement**: Any change to `homepage.css` must be verified against the 1600px desktop reference view.

## 4. Innovation Feature Verification
To definitively confirm the Innovation system is active:
1. **Visual Indicator**: Look for the "Innovation Monitor" panel at the bottom-left of the screen.
2. **Status Dots**: All three dots (Innovation System, Silence Mode, Seat Upgrade) must be **pulsating green**.
3. **Logic Confirmation**: 
   - Silence Mode: Triggers exactly 5 minutes before showtime.
   - Seat Upgrade: Triggers exactly 15 minutes before showtime.
4. **Polling**: System polls `api/innovation_checks.php` every 60 seconds.

## 5. Verification Checklist
- [ ] Grid columns are exactly 260px wide on desktop.
- [ ] Container width does not exceed 1600px.
- [ ] Logo height is 80px on desktop.
- [ ] Poster cards maintain 2/3 aspect ratio (260x390).
- [ ] Search bar width is 280px.
- [ ] Innovation Monitor is visible and active.
