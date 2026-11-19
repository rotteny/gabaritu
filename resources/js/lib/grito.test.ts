import { describe, expect, it } from 'vitest';
import { ehGrito, humorDaFala } from '@/lib/grito';

describe('ehGrito', () => {
    it('detecta fala toda em maiúscula', () => {
        expect(ehGrito('ISSO É UMA MITOCÔNDRIA, SEU TOTÓ!')).toBe(true);
    });

    it('ignora fala normal', () => {
        expect(ehGrito('Escolhe uma alternativa. Eu fico aqui.')).toBe(false);
    });

    it('exige mais de 60% das letras em maiúscula', () => {
        expect(ehGrito('ABCDEFG abc')).toBe(true);
        expect(ehGrito('ABCDEF abcd')).toBe(false);
    });

    it('ignora números e pontuação na conta', () => {
        expect(ehGrito('AI!!! 123 ...')).toBe(true);
        expect(ehGrito('123 ... !!!')).toBe(false);
        expect(ehGrito('')).toBe(false);
    });
});

describe('humorDaFala', () => {
    it('grito força o humor bravo', () => {
        expect(humorDaFala('NÃO ACREDITO!', 'neutro')).toBe('bravo');
    });

    it('fala normal mantém o humor', () => {
        expect(humorDaFala('Boa, acertou.', 'feliz')).toBe('feliz');
    });
});
