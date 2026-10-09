import { motion, useReducedMotion } from 'motion/react';
import type { ReactNode } from 'react';

type RevealProps = {
    children: ReactNode;
    className?: string;
    delay?: number;
    as?: 'div' | 'section' | 'article' | 'li';
};

/**
 * Membungkus elemen dengan animasi muncul saat elemen masuk viewport.
 */
export default function Reveal({
    children,
    className,
    delay = 0,
    as = 'div',
}: RevealProps) {
    const Component = motion[as];
    const reducedMotion = useReducedMotion();

    return (
        <Component
            className={className}
            initial={reducedMotion ? false : { opacity: 0, y: 18 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{
                duration: reducedMotion ? 0 : 0.45,
                delay: reducedMotion ? 0 : delay,
                ease: [0.22, 1, 0.36, 1],
            }}
        >
            {children}
        </Component>
    );
}
