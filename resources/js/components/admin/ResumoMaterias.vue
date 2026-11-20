<script setup lang="ts">
import type { ResumoAssunto } from '@/types/admin';

defineProps<{ materias: (ResumoAssunto & { assuntos: ResumoAssunto[] })[] }>();
const pct = (v: number | null) => (v === null ? '-' : `${v}%`);
</script>

<template>
    <div class="ad-lista">
        <details v-for="m in materias" :key="m.id" class="ad-cartao">
            <summary>
                <strong>{{ m.nome }}</strong>
                <span class="ad-mudo ad-num">
                    · {{ m.total }} questões ({{ m.autorais }} autorais,
                    {{ m.inep }} INEP) · {{ m.respostas }} respostas · acerto
                    {{ pct(m.pct_acerto) }}</span
                >
            </summary>
            <div class="ad-rolagem">
                <table class="ad-tabela">
                    <thead>
                        <tr>
                            <th scope="col">Assunto</th>
                            <th scope="col" class="ad-dir">Questões</th>
                            <th scope="col" class="ad-dir">Autorais</th>
                            <th scope="col" class="ad-dir">INEP</th>
                            <th scope="col" class="ad-dir">Respostas</th>
                            <th scope="col" class="ad-dir">Acerto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in m.assuntos" :key="a.id">
                            <td>{{ a.nome }}</td>
                            <td class="ad-dir">{{ a.total }}</td>
                            <td class="ad-dir">{{ a.autorais }}</td>
                            <td class="ad-dir">{{ a.inep }}</td>
                            <td class="ad-dir">{{ a.respostas }}</td>
                            <td class="ad-dir">{{ pct(a.pct_acerto) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</template>
