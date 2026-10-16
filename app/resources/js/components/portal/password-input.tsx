import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Eye, EyeOff } from 'lucide-react';
import * as React from 'react';
import { useState } from 'react';

/**
 * Campo de senha com botão para mostrar/ocultar o texto digitado.
 */
export const PasswordInput = React.forwardRef<HTMLInputElement, Omit<React.ComponentProps<'input'>, 'type'>>(({ className, ...props }, ref) => {
    const [visivel, setVisivel] = useState(false);

    return (
        <div className="relative">
            <Input ref={ref} type={visivel ? 'text' : 'password'} className={cn('pr-11', className)} {...props} />
            <button
                type="button"
                onClick={() => setVisivel((v) => !v)}
                className="text-muted-foreground hover:text-foreground focus-visible:ring-ring absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md outline-hidden focus-visible:ring-2"
                aria-label={visivel ? 'Ocultar senha' : 'Mostrar senha'}
                aria-pressed={visivel}
            >
                {visivel ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
            </button>
        </div>
    );
});
PasswordInput.displayName = 'PasswordInput';
