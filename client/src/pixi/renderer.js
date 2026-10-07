import { Application, Assets, Container, Sprite, Graphics } from 'pixi.js';
import { BARRIER_Y, DINO_Y } from '@/utils/collision';

/**
 * PixiJS v8 Renderer for Dino Road Cross
 * Purely visual — all game physics live in Vue's updateTraffic loop.
 * This renderer only: positions sprites, animates the dino, and pulses barriers.
 */
class Renderer {
  constructor(containerId) {
    this.containerId = containerId;
    this.app = null;

    // Incremental cache maps for performance (60fps)
    this.carsMap = new Map();    // id -> Container
    this.barriersMap = new Map(); // laneIdx -> Container

    this.dino = null;
    this.isCrashed = false;
    this.assetsLoaded = false;

    // Dino textures cache
    this.dinoTextures = { idle: [], jump: [], dead: [], walk: [], run: [] };
    this.barrierTexture = null;
    this.carTextures = [];

    // Dino state machine & smooth movement
    this.dinoState = 'idle';
    this.dinoFrame = 0;
    this.dinoAnimTimer = 0;
    // ms per frame for each state
    this.dinoAnimSpeed = { idle: 80, jump: 40, dead: 100, walk: 60, run: 50 };

    // Smooth lane jumping
    this.currentLane = 0;
    this.targetLane = 0;
    this.dinoX = 0;
    this.dinoY = 0;
    this.jumpProgress = 0;
    this.isJumping = false;
    this.maxJumpHeight = 60;
    this.laneWidth = 0;

    // Barrier glow phase
    this.barrierGlowPhase = 0;

    // Crash callback (set by Vue — kept for compatibility)
    this.onCrash = null;

    // Initialize Pixi App
    this._init();
  }

  async _init() {
    const container = document.getElementById(this.containerId);
    if (!container) {
      console.error('Pixi container not found:', this.containerId);
      return;
    }

    this.app = new Application();
    await this.app.init({
      backgroundAlpha: 0,
      antialias: true,
      resizeTo: container,
    });
    this.app.ticker.maxFPS = 60;

    container.appendChild(this.app.canvas);

    this.app.canvas.style.width = '100%';
    this.app.canvas.style.height = '100%';
    this.app.canvas.style.position = 'absolute';
    this.app.canvas.style.top = '0';
    this.app.canvas.style.left = '0';

    await this._loadAssets();
    this.app.ticker.add(this._update.bind(this));
  }

  async _loadAssets() {
    const frameCounts = { idle: 10, jump: 12, dead: 8, walk: 10, run: 8 };
    const prefixes   = { idle: 'Idle', jump: 'Jump', dead: 'Dead', walk: 'Walk', run: 'Run' };

    for (const state of ['idle', 'jump', 'dead', 'walk', 'run']) {
      const textures = [];
      for (let i = 1; i <= frameCounts[state]; i++) {
        const url = `/assets/dino/${state}/${prefixes[state]} (${i}).png`;
        try {
          textures.push(await Assets.load(url));
        } catch (e) {
          console.warn(`Missing dino frame: ${url}`);
        }
      }
      this.dinoTextures[state] = textures;
    }

    try {
      this.barrierTexture = await Assets.load('/assets/barrier.png');
    } catch (e) {
      console.warn('Missing barrier texture');
    }

    for (let i = 1; i <= 5; i++) {
      try {
        this.carTextures.push(await Assets.load(`/assets/cars/${i}.png`));
      } catch (e) {
        console.warn(`Missing car texture: ${i}.png`);
      }
    }

    this.assetsLoaded = true;
    this._createDino();
  }

  _createDino() {
    if (!this.dinoTextures.idle.length) return;

    this.dino = new Container();
    const sprite = new Sprite(this.dinoTextures.idle[0]);
    sprite.anchor.set(0.5, 0.5);

    const scale = 80 / Math.max(sprite.texture.width, sprite.texture.height);
    sprite.scale.set(scale);

    this.dino.addChild(sprite);

    this.laneWidth = this.app.renderer.width / 25;
    this.dinoX = 0.5 * this.laneWidth;
    this.dinoY = (DINO_Y / 100) * this.app.renderer.height;

    this.dino.x = this.dinoX;
    this.dino.y = this.dinoY;
    this.dino.zIndex = 100;

    this.app.stage.addChild(this.dino);
  }

  // ─── Dino Control ────────────────────────────────────────────────────────────

  setDinoPosition(laneIdx) {
    if (this.isCrashed) return;
    this.targetLane = laneIdx;
    if (this.targetLane !== this.currentLane && this.assetsLoaded) {
      this.isJumping = true;
      this.jumpProgress = 0;
      this._setDinoState('jump');
    }
  }

  triggerDeath() {
    this.isCrashed = true;
    this.isJumping = false;
    this._setDinoState('dead');
  }

  resetGame() {
    this.isCrashed = false;
    this.isJumping = false;
    this.targetLane = 0;
    this.currentLane = 0;
    this.laneWidth = this.app ? this.app.renderer.width / 25 : 0;
    this.dinoX = 0.5 * this.laneWidth;
    this.dinoY = (DINO_Y / 100) * (this.app ? this.app.renderer.height : 0);
    if (this.dino) {
      this.dino.x = this.dinoX;
      this.dino.y = this.dinoY;
    }
    this._setDinoState('idle');
  }

  /** Sets dino animation state, resetting frame counters. */
  _setDinoState(state) {
    this.dinoState = state;
    this.dinoFrame = 0;
    this.dinoAnimTimer = 0;
    if (this.dino && this.dinoTextures[state]?.length) {
      const sprite = this.dino.children[0];
      if (sprite) sprite.texture = this.dinoTextures[state][0];
    }
  }

  // ─── Car Management ──────────────────────────────────────────────────────────

  _createCarSprite(carData) {
    const car = new Container();
    const textureIdx = carData.colorIndex % Math.max(1, this.carTextures.length);
    const texture = this.carTextures[textureIdx];

    if (texture) {
      const sprite = new Sprite(texture);
      sprite.anchor.set(0.5, 0.5);
      car.addChild(sprite);
    } else {
      // Fallback coloured rectangle
      const g = new Graphics();
      const colors = [0xe74c3c, 0x3498db, 0x2ecc71, 0xf39c12, 0x9b59b6];
      g.roundRect(-20, -30, 40, 60, 6);
      g.fill(colors[carData.colorIndex % colors.length]);
      car.addChild(g);
    }

    car.carData = { ...carData };
    return car;
  }

  /**
   * Called every rAF frame from Vue with the latest car state.
   * Purely positions sprites — does NOT run physics.
   */
  syncCars(laneCars) {
    if (!this.assetsLoaded || !this.app) return;

    this.laneWidth = this.app.renderer.width / 25;
    const targetCarWidth = this.laneWidth * 0.85; // 85% of lane width
    const minCarHeight = this.laneWidth * 1.5;    // car min length = 1.5× lane
    const maxCarHeight = this.laneWidth * 2.5;    // car max length = 2.5× lane
    const currentIds = new Set();

    Object.entries(laneCars).forEach(([laneStr, cars]) => {
      const laneIdx = Number(laneStr);
      const laneX = (laneIdx + 0.5) * this.laneWidth;

      cars.forEach(carData => {
        currentIds.add(carData.id);

        let carContainer = this.carsMap.get(carData.id);
        if (!carContainer) {
          carContainer = this._createCarSprite(carData);
          this.carsMap.set(carData.id, carContainer);
          this.app.stage.addChild(carContainer);
        }

        // Sync position from Vue state
        carContainer.carData = { ...carData, laneIdx };
        carContainer.x = laneX;
        carContainer.y = (carData.y / 100) * this.app.renderer.height;

        // Scale sprite to fit lane width, clamping car height for proportion
        const child = carContainer.children[0];
        if (child instanceof Sprite) {
          const tex = child.texture;
          let scale;
          if (tex.width > tex.height) {
            // Landscape image — rotate so car faces upward
            child.rotation = -Math.PI / 2;
            // After rotation: displayed width = tex.height, displayed height = tex.width
            scale = targetCarWidth / tex.height;
            // Clamp rotated height (tex.width * scale) between min/max
            const rotH = tex.width * scale;
            if (rotH > maxCarHeight) scale = maxCarHeight / tex.width;
            else if (rotH < minCarHeight) scale = minCarHeight / tex.width;
          } else {
            // Portrait image — no rotation needed
            child.rotation = 0;
            scale = targetCarWidth / tex.width;
            // Clamp height (tex.height * scale) between min/max
            const h = tex.height * scale;
            if (h > maxCarHeight) scale = maxCarHeight / tex.height;
            else if (h < minCarHeight) scale = minCarHeight / tex.height;
          }
          child.scale.set(scale);
        } else if (child) {
          // Fallback Graphics — base rect is 40×60
          const scale = targetCarWidth / 40;
          child.scale.set(scale);
        }
      });
    });

    // Remove stale cars
    for (const [id, container] of this.carsMap.entries()) {
      if (!currentIds.has(id)) {
        container.parent?.removeChild(container);
        container.destroy({ children: true });
        this.carsMap.delete(id);
      }
    }
  }

  // ─── Barrier Management ───────────────────────────────────────────────────────

  _createBarrierSprite(laneIdx) {
    const barrier = new Container();

    if (this.barrierTexture) {
      const sprite = new Sprite(this.barrierTexture);
      sprite.anchor.set(0.5, 0.5);
      barrier.addChild(sprite);
    } else {
      const g = new Graphics();
      g.roundRect(-24, -6, 48, 12, 3);
      g.fill(0xf1c40f);
      g.stroke({ color: 0xe67e22, width: 3 });
      barrier.addChild(g);
    }

    this.laneWidth = this.app.renderer.width / 25;
    barrier.x = (laneIdx + 0.5) * this.laneWidth;
    barrier.y = (BARRIER_Y / 100) * this.app.renderer.height;
    return barrier;
  }

  syncBarriers(activeLanes) {
    if (!this.assetsLoaded || !this.app) return;

    this.laneWidth = this.app.renderer.width / 25;
    const targetBarrierWidth = this.laneWidth * 0.9;
    const activeSet = new Set(activeLanes);

    activeLanes.forEach(laneIdx => {
      let barrier = this.barriersMap.get(laneIdx);
      if (!barrier) {
        barrier = this._createBarrierSprite(laneIdx);
        this.barriersMap.set(laneIdx, barrier);
        this.app.stage.addChild(barrier);
      }

      const child = barrier.children[0];
      if (child instanceof Sprite) {
        child.scale.set(targetBarrierWidth / child.texture.width);
      } else if (child) {
        child.scale.set(targetBarrierWidth / 48);
      }

      barrier.x = (laneIdx + 0.5) * this.laneWidth;
      barrier.y = (BARRIER_Y / 100) * this.app.renderer.height;
    });

    for (const [laneIdx, barrier] of this.barriersMap.entries()) {
      if (!activeSet.has(laneIdx)) {
        barrier.parent?.removeChild(barrier);
        barrier.destroy({ children: true });
        this.barriersMap.delete(laneIdx);
      }
    }
  }

  // ─── Main Ticker ─────────────────────────────────────────────────────────────

  _update(ticker) {
    if (!this.assetsLoaded || !this.app) return;
    const delta = ticker.deltaTime; // in frames (1 = one 60fps frame)
    const deltaMs = delta * (1000 / 60); // convert to ms

    this.laneWidth = this.app.renderer.width / 25;

    // Pulse barrier alpha
    this.barrierGlowPhase += delta * 0.12;
    const pulseAlpha = 0.75 + Math.sin(this.barrierGlowPhase) * 0.25;
    this.barriersMap.forEach(b => { b.alpha = pulseAlpha; });

    // Move Dino toward target lane with jump arc
    this._updateDinoPhysics(delta);

    // Advance dino sprite frames
    this._updateDinoAnim(deltaMs);
  }

  _updateDinoPhysics(delta) {
    if (!this.dino || this.isCrashed) return;

    const targetX  = (this.targetLane + 0.5) * this.laneWidth;
    const baseDinoY = (DINO_Y / 100) * this.app.renderer.height;

    if (this.isJumping) {
      this.jumpProgress += delta * 0.05;
      if (this.jumpProgress >= 1) {
        this.jumpProgress = 1;
        this.isJumping = false;
        this.currentLane = this.targetLane;
        this._setDinoState('idle');
      }

      const startX = (this.currentLane + 0.5) * this.laneWidth;
      this.dinoX = startX + (targetX - startX) * this.jumpProgress;
      this.dinoY = baseDinoY - this.maxJumpHeight * Math.sin(this.jumpProgress * Math.PI);
    } else {
      this.dinoX = targetX;
      this.dinoY = baseDinoY;
      this.currentLane = this.targetLane;
    }

    this.dino.x = this.dinoX;
    this.dino.y = this.dinoY;
  }

  _updateDinoAnim(deltaMs) {
    if (!this.dino) return;

    const frames = this.dinoTextures[this.dinoState];
    if (!frames || frames.length === 0) return;

    this.dinoAnimTimer += deltaMs;
    const speed = this.dinoAnimSpeed[this.dinoState];

    if (this.dinoAnimTimer >= speed) {
      this.dinoAnimTimer -= speed; // accumulate remainder for smooth timing

      if (this.dinoState === 'dead') {
        // Play death animation once, hold last frame
        if (this.dinoFrame < frames.length - 1) {
          this.dinoFrame++;
          const sprite = this.dino.children[0];
          if (sprite && frames[this.dinoFrame]) {
            sprite.texture = frames[this.dinoFrame];
          }
        }
      } else {
        this.dinoFrame = (this.dinoFrame + 1) % frames.length;
        const sprite = this.dino.children[0];
        if (sprite && frames[this.dinoFrame]) {
          sprite.texture = frames[this.dinoFrame];
        }
      }
    }
  }

  // ─── Cleanup ─────────────────────────────────────────────────────────────────

  destroy() {
    for (const c of this.carsMap.values())     { c.parent?.removeChild(c); c.destroy({ children: true }); }
    for (const b of this.barriersMap.values())  { b.parent?.removeChild(b); b.destroy({ children: true }); }
    this.carsMap.clear();
    this.barriersMap.clear();

    if (this.dino) {
      this.dino.parent?.removeChild(this.dino);
      this.dino.destroy({ children: true });
      this.dino = null;
    }

    if (this.app) {
      this.app.destroy(true, { children: true, texture: false });
      this.app = null;
    }
  }
}

export const createRenderer = (containerId) => new Renderer(containerId);
