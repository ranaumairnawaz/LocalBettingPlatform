<script setup>
import { computed } from 'vue'

const props = defineProps({
  boardState: { type: Object, required: true },
  myColor: { type: String, required: true }
})

const emit = defineEmits(['movePiece'])

const globalPath = [
    [6,1], [6,2], [6,3], [6,4], [6,5],
    [5,6], [4,6], [3,6], [2,6], [1,6], [0,6],
    [0,7], [0,8],
    [1,8], [2,8], [3,8], [4,8], [5,8],
    [6,9], [6,10], [6,11], [6,12], [6,13], [6,14],
    [7,14], [8,14],
    [8,13], [8,12], [8,11], [8,10], [8,9],
    [9,8], [10,8], [11,8], [12,8], [13,8], [14,8],
    [14,7], [14,6],
    [13,6], [12,6], [11,6], [10,6], [9,6],
    [8,5], [8,4], [8,3], [8,2], [8,1], [8,0],
    [7,0], [6,0]
];

const homeStretches = {
    red: [[7,1], [7,2], [7,3], [7,4], [7,5]],
    green: [[1,7], [2,7], [3,7], [4,7], [5,7]],
    yellow: [[7,13], [7,12], [7,11], [7,10], [7,9]],
    blue: [[13,7], [12,7], [11,7], [10,7], [9,7]]
};

const homes = {
    red: [7,6],
    green: [6,7],
    yellow: [7,8],
    blue: [8,7]
};

const basePositions = {
    red: [[2,2], [2,3], [3,2], [3,3]],
    green: [[2,11], [2,12], [3,11], [3,12]],
    yellow: [[11,11], [11,12], [12,11], [12,12]],
    blue: [[11,2], [11,3], [12,2], [12,3]]
};

const offsets = { red: 0, green: 13, yellow: 26, blue: 39 };

const getPiecePosition = (color, localPos, pieceIndex) => {
    if (localPos === 0) {
        return basePositions[color][pieceIndex];
    } else if (localPos <= 51) {
        const globalIndex = (offsets[color] + localPos - 1) % 52;
        return globalPath[globalIndex];
    } else if (localPos <= 56) {
        return homeStretches[color][localPos - 52];
    } else {
        return homes[color];
    }
};

const piecesToRender = computed(() => {
    let pieces = [];
    if (!props.boardState || !props.boardState.pieces) return pieces;
    
    // Group pieces by their coordinate to handle overlaps
    let coordMap = {};

    for (const color of ['red', 'green', 'yellow', 'blue']) {
        if (!props.boardState.pieces[color]) continue;
        
        props.boardState.pieces[color].forEach((localPos, pIndex) => {
            const [row, col] = getPiecePosition(color, localPos, pIndex);
            const key = `${row}-${col}`;
            if (!coordMap[key]) coordMap[key] = [];
            coordMap[key].push({ color, localPos, pIndex, row, col });
        });
    }

    // Now format pieces with slight offsets if they share the same square
    for (const key in coordMap) {
        const squarePieces = coordMap[key];
        const count = squarePieces.length;
        
        squarePieces.forEach((p, idx) => {
            // If multiple pieces are on the same track square, shift them slightly
            let offsetX = 0;
            let offsetY = 0;
            if (count > 1 && p.localPos > 0 && p.localPos < 57) {
                const angle = (idx / count) * Math.PI * 2;
                offsetX = Math.cos(angle) * 15;
                offsetY = Math.sin(angle) * 15;
            }
            
            const isMyTurn = props.boardState.turn_color === props.myColor;
            const isMyPiece = p.color === props.myColor;
            const canMove = isMyTurn && isMyPiece && props.boardState.dice_value !== null;
            
            pieces.push({
                ...p,
                style: {
                    top: `calc(${(p.row / 15) * 100}% + ${offsetY}px)`,
                    left: `calc(${(p.col / 15) * 100}% + ${offsetX}px)`,
                    width: `${100/15}%`,
                    height: `${100/15}%`
                },
                canMove
            });
        });
    }
    return pieces;
});

const handlePieceClick = (piece) => {
    if (piece.canMove) {
        emit('movePiece', piece.pIndex);
    }
};

const safeSquares = [
    globalPath[0], globalPath[8], globalPath[13], globalPath[21],
    globalPath[26], globalPath[34], globalPath[39], globalPath[47]
];

const isSafe = (r, c) => safeSquares.some(s => s[0] === r && s[1] === c);

</script>

<template>
  <div class="relative w-full max-w-[500px] aspect-square bg-white shadow-2xl rounded-lg overflow-hidden border-8 border-zinc-300 mx-auto">
    
    <!-- Draw Grid -->
    <div class="absolute inset-0 grid grid-cols-[repeat(15,minmax(0,1fr))] grid-rows-[repeat(15,minmax(0,1fr))]">
        <!-- Loop 225 cells -->
        <div v-for="i in 225" :key="i" class="border-[0.5px] border-zinc-200"></div>
    </div>

    <!-- Bases -->
    <div class="absolute top-0 left-0 w-[40%] h-[40%] bg-red-600 border-[12px] border-white flex items-center justify-center">
        <div class="w-[60%] h-[60%] bg-white rounded-2xl"></div>
    </div>
    <div class="absolute top-0 right-0 w-[40%] h-[40%] bg-green-600 border-[12px] border-white flex items-center justify-center">
        <div class="w-[60%] h-[60%] bg-white rounded-2xl"></div>
    </div>
    <div class="absolute bottom-0 right-0 w-[40%] h-[40%] bg-yellow-500 border-[12px] border-white flex items-center justify-center">
        <div class="w-[60%] h-[60%] bg-white rounded-2xl"></div>
    </div>
    <div class="absolute bottom-0 left-0 w-[40%] h-[40%] bg-blue-500 border-[12px] border-white flex items-center justify-center">
        <div class="w-[60%] h-[60%] bg-white rounded-2xl"></div>
    </div>

    <!-- Home Triangles -->
    <div class="absolute top-[40%] left-[40%] w-[20%] h-[20%]">
        <svg viewBox="0 0 100 100" class="w-full h-full">
            <polygon points="0,0 50,50 0,100" fill="#3b82f6" />
            <polygon points="0,0 100,0 50,50" fill="#dc2626" />
            <polygon points="100,0 100,100 50,50" fill="#16a34a" />
            <polygon points="0,100 100,100 50,50" fill="#eab308" />
        </svg>
    </div>

    <!-- Home Stretches colors -->
    <div v-for="cell in homeStretches.red" :key="'hs-r-'+cell[0]+'-'+cell[1]" class="absolute bg-red-600 border border-zinc-300" :style="{ top: `${(cell[0]/15)*100}%`, left: `${(cell[1]/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }"></div>
    <div v-for="cell in homeStretches.green" :key="'hs-g-'+cell[0]+'-'+cell[1]" class="absolute bg-green-600 border border-zinc-300" :style="{ top: `${(cell[0]/15)*100}%`, left: `${(cell[1]/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }"></div>
    <div v-for="cell in homeStretches.yellow" :key="'hs-y-'+cell[0]+'-'+cell[1]" class="absolute bg-yellow-500 border border-zinc-300" :style="{ top: `${(cell[0]/15)*100}%`, left: `${(cell[1]/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }"></div>
    <div v-for="cell in homeStretches.blue" :key="'hs-b-'+cell[0]+'-'+cell[1]" class="absolute bg-blue-500 border border-zinc-300" :style="{ top: `${(cell[0]/15)*100}%`, left: `${(cell[1]/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }"></div>

    <!-- Safe squares / Stars -->
    <div v-for="cell in safeSquares" :key="'safe-'+cell[0]+'-'+cell[1]" class="absolute flex items-center justify-center opacity-40 pointer-events-none" :style="{ top: `${(cell[0]/15)*100}%`, left: `${(cell[1]/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }">
        ⭐
    </div>

    <!-- Start squares colored -->
    <div class="absolute bg-red-600/30" :style="{ top: `${(6/15)*100}%`, left: `${(1/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }"></div>
    <div class="absolute bg-green-600/30" :style="{ top: `${(1/15)*100}%`, left: `${(8/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }"></div>
    <div class="absolute bg-yellow-500/30" :style="{ top: `${(8/15)*100}%`, left: `${(13/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }"></div>
    <div class="absolute bg-blue-500/30" :style="{ top: `${(13/15)*100}%`, left: `${(6/15)*100}%`, width: `${100/15}%`, height: `${100/15}%` }"></div>

    <!-- PIECES -->
    <div 
        v-for="(piece, i) in piecesToRender" 
        :key="'p-'+piece.color+'-'+piece.pIndex"
        class="absolute flex items-center justify-center transition-all duration-300 ease-out z-10"
        :style="piece.style"
    >
        <button 
            @click="handlePieceClick(piece)"
            class="w-[70%] h-[70%] rounded-full shadow-[0_4px_6px_rgba(0,0,0,0.5),inset_0_-3px_5px_rgba(0,0,0,0.3),inset_0_3px_5px_rgba(255,255,255,0.5)] border-[2px] border-white/80 transition-transform flex items-center justify-center"
            :class="[
                piece.canMove ? 'cursor-pointer hover:scale-125 animate-bounce ring-4 ring-white/50 z-20' : 'cursor-default',
                piece.color === 'red' ? 'bg-red-600' : 
                piece.color === 'green' ? 'bg-green-600' : 
                piece.color === 'yellow' ? 'bg-yellow-500' : 'bg-blue-500'
            ]"
        >
            <div class="w-1/3 h-1/3 rounded-full bg-white/30"></div>
        </button>
    </div>

  </div>
</template>
