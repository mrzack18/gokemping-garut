import { Button } from '@/components/ui/button';

type BookingMobileBarProps = {
    amount: string;
    label: string;
    disabled?: boolean;
    onClick?: () => void;
    formId?: string;
};

export default function BookingMobileBar({
    amount,
    label,
    disabled = false,
    onClick,
    formId,
}: BookingMobileBarProps) {
    return (
        <div className="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur lg:hidden">
            <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-1">
                <div className="min-w-0">
                    <p className="text-[0.65rem] font-medium tracking-wide text-muted-foreground uppercase">
                        Total estimasi
                    </p>
                    <p className="truncate text-sm font-semibold tabular-nums">
                        {amount}
                    </p>
                </div>
                <Button
                    type={formId ? 'submit' : 'button'}
                    form={formId}
                    onClick={onClick}
                    disabled={disabled}
                    className="shrink-0"
                >
                    {label}
                </Button>
            </div>
        </div>
    );
}
