import { motion, type Variants } from 'motion/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

const variants: Variants = {
    hidden: { opacity: 0, y: 24 },
    visible: {
        opacity: 1,
        y: 0,
        transition: { duration: 0.5, ease: [0.22, 1, 0.36, 1] },
    },
};

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

    return (
        <Component
            className={cn(className)}
            variants={variants}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, amount: 0.2 }}
            transition={{ delay }}
        >
            {children}
        </Component>
    );
}
