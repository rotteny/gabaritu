/** Avatares de animais marinhos do aluno. SVGs em public/avatares/{slug}.svg (e PNG 2x em public/email/avatares). */
export const AVATARES = [
    { slug: 'peixe-palhaco', nome: 'Peixe-palhaço' },
    { slug: 'caranguejo', nome: 'Caranguejo' },
    { slug: 'polvo', nome: 'Polvo' },
    { slug: 'tartaruga', nome: 'Tartaruga' },
    { slug: 'agua-viva', nome: 'Água-viva' },
    { slug: 'cavalo-marinho', nome: 'Cavalo-marinho' },
    { slug: 'estrela-do-mar', nome: 'Estrela-do-mar' },
    { slug: 'golfinho', nome: 'Golfinho' },
    { slug: 'baleia', nome: 'Baleia' },
    { slug: 'lula', nome: 'Lula' },
    { slug: 'ourico', nome: 'Ouriço-do-mar' },
    { slug: 'lagosta', nome: 'Lagosta' },
] as const;

export type AvatarSlug = (typeof AVATARES)[number]['slug'];

export const nomeDoAvatar = (slug: string): string =>
    AVATARES.find((a) => a.slug === slug)?.nome ?? 'Avatar';
export const AVATAR_PADRAO: AvatarSlug = 'peixe-palhaco';
