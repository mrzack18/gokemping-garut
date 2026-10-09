import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * Input berkas dengan gaya seragam untuk seluruh panel admin. Tombol
 * `file:` bawaan peramban disamakan dengan tombol sekunder agar tidak
 * terlihat seperti kontrol yang belum didesain.
 */
export default function FileInput({
    className,
    ...props
}: ComponentProps<'input'>) {
    return (
        <input
            type="file"
            className={cn(
                'block w-full text-sm text-muted-foreground',
                'file:mr-3 file:h-8 file:cursor-pointer file:rounded-md file:border file:border-input file:bg-secondary file:px-3 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80',
                'disabled:cursor-not-allowed disabled:opacity-60',
                className,
            )}
            {...props}
        />
    );
}
