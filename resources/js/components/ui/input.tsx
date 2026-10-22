import * as React from "react"

import { cn } from "@/lib/utils"

function Input({ className, type, ...props }: React.ComponentProps<"input">) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        "flex h-10 w-full min-w-0 border-0 border-b border-input bg-field px-4 py-1 text-sm tracking-[0.16px] text-foreground transition-colors outline-none placeholder:text-subtle-foreground selection:bg-primary selection:text-primary-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground disabled:cursor-not-allowed disabled:border-transparent disabled:text-subtle-foreground",
        "focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring",
        "aria-invalid:outline-2 aria-invalid:-outline-offset-2 aria-invalid:outline-destructive-foreground",
        className
      )}
      {...props}
    />
  )
}

export { Input }
