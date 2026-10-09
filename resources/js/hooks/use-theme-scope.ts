import { useEffect } from 'react';

/**
 * Menempelkan class tema pada `<body>` selama halaman bertema ini terpasang.
 *
 * Efek ke `<body>` diperlukan karena portal Radix (Sheet, Dialog, Select,
 * DropdownMenu) merender di luar pohon komponen halaman, sehingga variabel
 * CSS yang hanya di-scope ke wrapper halaman tidak ikut terbawa ke portal.
 */
export function useThemeScope(className: string): void {
    useEffect(() => {
        document.body.classList.add(className);

        return () => {
            document.body.classList.remove(className);
        };
    }, [className]);
}
