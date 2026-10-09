import { Check } from 'lucide-react';

const labels = ['Jadwal', 'Biodata', 'Review', 'Pembayaran'];

type BookingStepsProps = {
    current: 1 | 2 | 3 | 4;
    complete?: boolean;
};

export default function BookingSteps({
    current,
    complete = false,
}: BookingStepsProps) {
    return (
        <nav aria-label="Progres pemesanan" className="relative">
            <div
                aria-hidden="true"
                className="absolute top-3 right-[12.5%] left-[12.5%] border-t border-border"
            />
            <ol className="relative grid grid-cols-4 gap-1">
                {labels.map((label, index) => {
                    const step = index + 1;
                    const done = complete || step < current;
                    const active = !complete && step === current;

                    return (
                        <li
                            key={label}
                            aria-current={active ? 'step' : undefined}
                            className="flex min-w-0 flex-col items-center gap-1.5 text-center"
                        >
                            <span
                                className={`relative z-10 flex size-6 items-center justify-center rounded-full border text-[0.65rem] font-semibold ${
                                    done || active
                                        ? 'border-pine-700 bg-pine-700 text-white dark:border-pine-700 dark:bg-pine-700 dark:text-pine-950'
                                        : 'border-border bg-background text-muted-foreground'
                                }`}
                            >
                                {done ? (
                                    <Check
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                ) : (
                                    step
                                )}
                            </span>
                            <span
                                className={`text-[0.65rem] leading-tight sm:text-xs ${
                                    active
                                        ? 'font-semibold text-foreground'
                                        : done
                                          ? 'text-muted-foreground'
                                          : 'text-muted-foreground/75'
                                }`}
                            >
                                {label}
                            </span>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
