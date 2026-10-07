# 666Win Client

This is the frontend client application for the 666Win game portal, built using Vue 3 and Vite.

## PixiJS 2D Side-Scrolling Game Engine Integration

We migrated the **Dino Road Cross** game from a CSS/SVG-based rendering system to **PixiJS (v8)** to deliver arcade-grade performance, responsive scaling, and smooth physics.

### Architecture Overview

- **Vue 3 View Layer (`DinoCrossView.vue`)**: Manages the betting system, user authentication, game logs, safe zones, and coordinates high-level state changes.
- **PixiJS Renderer Layer (`renderer.js`)**: Initializes the WebGL canvas, loads assets (Dino frame-by-frame sprites, textured barriers), implements the 60fps render loop, and handles smooth visual rendering of cars, barriers, and Dino physics.

### Key Enhancements

1. **Vibrant Textured Sprites**:
   - The Dinosaur character loads high-fidelity PNG frame animations dynamically for all states (`idle`, `walk`, `run`, `jump`, `dead`).
   - The safety barriers load textured barrier sprites from `/assets/barrier.png`.
   - All assets are pre-loaded via the PixiJS v8 `Assets` API.

2. **Incremental Sprite Caching (60fps Optimization)**:
   - Instead of tearing down and recreating Pixi sprites on every update frame, we implement object pools/maps (`carsMap`, `barriersMap`).
   - Sprites are updated in-place incrementally, yielding rock-solid 60fps rendering without garbage collection pauses.

3. **Smooth Parabolic Jump Curves**:
   - The Dinosaur's movement between lanes is handled by a smooth linear interpolation on the X-axis combined with a parabolic sine arc on the Y-axis.
   - This creates a realistic, arcade-like hop animation when transitioning lanes.

4. **Single-Viewport Overlay**:
   - We mount a single Pixi WebGL Canvas overlaying the entire multi-lane `Road Track`.
   - The canvas automatically scales to match the parent container's aspect ratio and dimensions, adapting to desktop and mobile layouts.

### Running Unit Tests

Unit tests are written using standard mocking for the Pixi renderer and cover all engine logic:
- Initialization of the Pixi Canvas
- Incremental update mapping of vehicles
- Lane boundary checks
- Dinosaur jump state machine transitions

Run client tests:
```bash
npm run test
```
(Ensure Vitest is configured/installed in your environment).
