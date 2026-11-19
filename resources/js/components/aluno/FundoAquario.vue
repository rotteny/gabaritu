<script setup lang="ts">
const bolhas = [
    { x: '6%', s: '18px', t: '16s', d: '0s' },
    { x: '22%', s: '26px', t: '21s', d: '-6s' },
    { x: '41%', s: '14px', t: '14s', d: '-3s' },
    { x: '63%', s: '30px', t: '24s', d: '-11s' },
    { x: '79%', s: '16px', t: '17s', d: '-8s' },
    { x: '93%', s: '22px', t: '19s', d: '-2s' },
];
</script>

<template>
    <div aria-hidden="true">
        <svg
            class="gb-cena gb-cena--mob"
            viewBox="0 0 400 700"
            preserveAspectRatio="xMidYMax slice"
        >
            <rect class="gb-agua" width="400" height="700" />
            <polygon class="gb-raio" points="40,0 100,0 40,700 -50,700" />
            <polygon class="gb-raio" points="190,0 235,0 200,700 120,700" />
            <polygon class="gb-raio" points="310,0 360,0 360,700 270,700" />
            <path class="gb-alga-o" d="M14 690 Q-2 650 16 628 Q32 608 14 584" />
            <path class="gb-alga" d="M14 690 Q-2 650 16 628 Q32 608 14 584" />
            <path
                class="gb-areia"
                d="M-6 682 Q60 668 130 680 T260 678 T406 682 V710 H-6Z"
            />
            <ellipse class="gb-pedra" cx="190" cy="692" rx="18" ry="9" />
        </svg>
        <svg
            class="gb-cena gb-cena--desk"
            viewBox="0 0 1366 768"
            preserveAspectRatio="xMidYMax slice"
        >
            <rect class="gb-agua" width="1366" height="768" />
            <polygon class="gb-raio" points="100,0 200,0 80,768 -60,768" />
            <polygon class="gb-raio" points="520,0 600,0 540,768 420,768" />
            <polygon class="gb-raio" points="900,0 960,0 1000,768 880,768" />
            <path class="gb-alga-o" d="M40 750 Q10 700 44 668 Q70 640 40 600" />
            <path class="gb-alga" d="M40 750 Q10 700 44 668 Q70 640 40 600" />
            <path class="gb-alga-o" d="M1320 752 Q1296 710 1324 680" />
            <path class="gb-alga" d="M1320 752 Q1296 710 1324 680" />
            <path
                class="gb-areia"
                d="M-6 742 Q200 724 400 740 T800 738 T1200 740 T1372 742 V780 H-6Z"
            />
            <ellipse class="gb-pedra" cx="640" cy="752" rx="26" ry="11" />
        </svg>
        <div class="gb-bolhas">
            <span
                v-for="b in bolhas"
                :key="b.x"
                class="gb-bolha"
                :style="{ '--x': b.x, '--s': b.s, '--t': b.t, '--d': b.d }"
            />
        </div>
    </div>
</template>
