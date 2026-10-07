import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createRenderer } from '../../src/pixi/renderer';

// Mock PixiJS dependencies so the tests can run headlessly in any environment
vi.mock('pixi.js', () => {
  class MockApplication {
    init = vi.fn().mockResolvedValue(undefined);
    ticker = {
      add: vi.fn(),
      maxFPS: 60
    };
    stage = {
      addChild: vi.fn(),
      removeChild: vi.fn()
    };
    renderer = {
      width: 1000,
      height: 600
    };
    canvas = {
      style: {}
    };
    destroy = vi.fn();
  }

  class MockContainer {
    addChild = vi.fn();
    removeChild = vi.fn();
    destroy = vi.fn();
    x = 0;
    y = 0;
    children = [{ scale: { set: vi.fn() } }];
  }

  class MockSprite {
    anchor = { set: vi.fn() };
    scale = { set: vi.fn() };
    destroy = vi.fn();
  }

  class MockGraphics {
    roundRect = vi.fn().mockReturnThis();
    fill = vi.fn().mockReturnThis();
    circle = vi.fn().mockReturnThis();
    stroke = vi.fn().mockReturnThis();
    destroy = vi.fn();
  }

  return {
    Application: MockApplication,
    Container: MockContainer,
    Sprite: MockSprite,
    Graphics: MockGraphics,
    Assets: {
      load: vi.fn().mockResolvedValue({})
    }
  };
});

describe('Renderer (PixiJS integration)', () => {
  let mockContainer;

  beforeEach(() => {
    // Setup Mock DOM container
    mockContainer = document.createElement('div');
    mockContainer.id = 'pixiContainer';
    document.body.appendChild(mockContainer);
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  it('should initialize Pixi Application correctly', async () => {
    const renderer = createRenderer('pixiContainer');
    await renderer._init();

    expect(renderer.app).toBeDefined();
    expect(renderer.app.canvas.style.width).toBe('100%');
    expect(renderer.app.canvas.style.height).toBe('100%');
  });

  it('should sync cars into carsMap', async () => {
    const renderer = createRenderer('pixiContainer');
    await renderer._init();
    renderer.assetsLoaded = true;

    const laneCars = {
      1: [{ id: 101, y: 80, speed: 1.2, type: 'sedan', colorIndex: 0 }],
      2: [{ id: 102, y: 50, speed: 1.5, type: 'bus', colorIndex: 3 }]
    };

    renderer.syncCars(laneCars);

    expect(renderer.carsMap.size).toBe(2);
    expect(renderer.carsMap.has(101)).toBe(true);
    expect(renderer.carsMap.has(102)).toBe(true);

    // Sync again with updated Y and removed car
    const updatedLaneCars = {
      1: [{ id: 101, y: 75, speed: 1.2, type: 'sedan', colorIndex: 0 }]
    };

    renderer.syncCars(updatedLaneCars);

    expect(renderer.carsMap.size).toBe(1);
    expect(renderer.carsMap.has(101)).toBe(true);
    expect(renderer.carsMap.has(102)).toBe(false);
  });

  it('should sync barriers into barriersMap', async () => {
    const renderer = createRenderer('pixiContainer');
    await renderer._init();
    renderer.assetsLoaded = true;

    renderer.syncBarriers([3, 5]);

    expect(renderer.barriersMap.size).toBe(2);
    expect(renderer.barriersMap.has(3)).toBe(true);
    expect(renderer.barriersMap.has(5)).toBe(true);

    renderer.syncBarriers([3]);

    expect(renderer.barriersMap.size).toBe(1);
    expect(renderer.barriersMap.has(3)).toBe(true);
    expect(renderer.barriersMap.has(5)).toBe(false);
  });

  it('should set dino position and trigger jump state', async () => {
    const renderer = createRenderer('pixiContainer');
    await renderer._init();
    renderer.assetsLoaded = true;
    renderer._createDino();

    renderer.setDinoPosition(4);

    expect(renderer.targetLane).toBe(4);
    expect(renderer.isJumping).toBe(true);
    expect(renderer.dinoState).toBe('jump');
  });

  it('should trigger death state on dino', async () => {
    const renderer = createRenderer('pixiContainer');
    await renderer._init();
    renderer.assetsLoaded = true;
    renderer._createDino();

    renderer.triggerDeath();

    expect(renderer.isCrashed).toBe(true);
    expect(renderer.dinoState).toBe('dead');
  });

  it('should reset game state on resetGame', async () => {
    const renderer = createRenderer('pixiContainer');
    await renderer._init();
    renderer.assetsLoaded = true;
    renderer._createDino();

    renderer.triggerDeath();
    renderer.resetGame();

    expect(renderer.isCrashed).toBe(false);
    expect(renderer.dinoState).toBe('idle');
  });

  it('should clean up all assets and App on destroy', async () => {
    const renderer = createRenderer('pixiContainer');
    await renderer._init();
    renderer.assetsLoaded = true;
    
    renderer.syncBarriers([1]);
    renderer.syncCars({ 1: [{ id: 101, y: 80, speed: 1, type: 'sedan', colorIndex: 0 }] });

    renderer.destroy();

    expect(renderer.app).toBeNull();
    expect(renderer.carsMap.size).toBe(0);
    expect(renderer.barriersMap.size).toBe(0);
  });
});
