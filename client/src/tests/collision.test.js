import { checkBarrierCollision, checkDinoCollision, BARRIER_Y, DINO_Y, HITBOX_MARGIN } from '../utils/collision.js';

/**
 * Simple Unit Test Runner for Collision Logic
 */
function runTests() {
    console.log('--- Running Collision Logic Tests ---');

    // Test 1: Vehicle movement direction (bottom-to-top implies decreasing Y)
    console.log('Test 1: Bottom-to-top movement consistency...');
    const initialY = 100;
    const speed = 2;
    const result = checkBarrierCollision(initialY, speed, false);
    if (result.nextY < initialY) {
        console.log('✅ Pass: Car moves up (Y decreases)');
    } else {
        console.error('❌ Fail: Car does not move up');
    }

    // Test 2: Barrier collision detection accuracy
    console.log('Test 2: Barrier stops car before contact...');
    const carNearBarrierY = BARRIER_Y + HITBOX_MARGIN + 1; // Just below stop point
    const collisionResult = checkBarrierCollision(carNearBarrierY, 2, true);
    if (collisionResult.stop && collisionResult.nextY === BARRIER_Y + HITBOX_MARGIN) {
        console.log('✅ Pass: Car stops at barrier point');
    } else {
        console.error('❌ Fail: Car crossed barrier or didn\'t stop correctly');
    }

    // Test 3: No crossing rule
    console.log('Test 3: Car cannot pass active barrier...');
    const carAtBarrierY = BARRIER_Y + HITBOX_MARGIN;
    const stayAtBarrierResult = checkBarrierCollision(carAtBarrierY, 2, true);
    if (stayAtBarrierResult.stop && stayAtBarrierResult.nextY === BARRIER_Y + HITBOX_MARGIN) {
        console.log('✅ Pass: Car remains stopped at barrier');
    } else {
        console.error('❌ Fail: Car passed through active barrier');
    }

    // Test 4: Barrier deactivation allows passing
    console.log('Test 4: Deactivated barrier allows crossing...');
    const deactivatedResult = checkBarrierCollision(carAtBarrierY, 2, false);
    if (!deactivatedResult.stop && deactivatedResult.nextY < carAtBarrierY) {
        console.log('✅ Pass: Car passes when barrier is inactive');
    } else {
        console.error('❌ Fail: Car still stopped by inactive barrier');
    }

    // Test 5: Dino death trigger only after barrier removal
    console.log('Test 5: Dino protected by active barrier...');
    const carAtDinoY = DINO_Y;
    const protectedResult = checkDinoCollision(carAtDinoY, DINO_Y, true);
    if (!protectedResult) {
        console.log('✅ Pass: Dino is protected by active barrier');
    } else {
        console.error('❌ Fail: Dino hit while barrier was active');
    }

    console.log('Test 6: Car hits Dino after barrier removal...');
    const unprotectedResult = checkDinoCollision(carAtDinoY, DINO_Y, false);
    if (unprotectedResult) {
        console.log('✅ Pass: Car registers hit on Dino without barrier');
    } else {
        console.error('❌ Fail: Hit not registered after barrier removal');
    }

    console.log('--- Tests Completed ---');
}

runTests();
