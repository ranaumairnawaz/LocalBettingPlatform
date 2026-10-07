/**
 * Collision detection utility for Dino Road Cross
 */

export const VEHICLE_DIRECTION = 'BOTTOM_TO_TOP';
export const BARRIER_Y = 60; // Percentage from top
export const DINO_Y = 50;    // Percentage from top
export const HITBOX_MARGIN = 2; // Margin for discrete hitbox detection

/**
 * Checks if a vehicle should stop for a barrier
 * @param {number} carY - Current Y position of the car (percentage from top)
 * @param {number} carSpeed - Current speed of the car (percentage per frame)
 * @param {boolean} barrierActive - Whether the barrier is active
 * @returns {Object} - { stop: boolean, nextY: number }
 */
export function checkBarrierCollision(carY, carSpeed, barrierActive) {
    if (!barrierActive) return { stop: false, nextY: carY - carSpeed };

    // Car is below the barrier and moving up
    const nextY = carY - carSpeed;
    const barrierStopPoint = BARRIER_Y + HITBOX_MARGIN;

    if (carY >= barrierStopPoint && nextY < barrierStopPoint) {
        return { stop: true, nextY: barrierStopPoint };
    }

    return { stop: false, nextY };
}

/**
 * Checks if a vehicle has hit the Dino
 * @param {number} carY_old - Previous Y position
 * @param {number} carY_new - Current Y position
 * @param {number} dinoY - Dino Y position
 * @param {boolean} barrierActive - Whether the barrier is active
 * @returns {boolean}
 */
export function checkDinoCollision(carY_old, carY_new, dinoY, barrierActive) {
    if (barrierActive) return false;

    // The car is moving BOTTOM-TO-TOP, so carY is DECREASING.
    // Dino is centered at dinoY.
    const dinoHitboxHeight = 10; // Dino is roughly 10% of screen height
    const dinoTop = dinoY - (dinoHitboxHeight / 2);
    const dinoBottom = dinoY + (dinoHitboxHeight / 2);

    // A collision occurs if the car's path in this frame intersects the Dino's hitbox.
    // Since car is moving up, carY_old > carY_new.
    return (carY_old >= dinoTop && carY_new <= dinoBottom);
}
