import * as React from "react"
import { cn } from "@/lib/utils"

function Textarea({ className, ...props }: React.ComponentProps<"textarea">) {
  return (
    <textarea
      data-slot="textarea"
      className={cn(
        "flex field-sizing-content min-h-22 w-full border-0 border-b border-input bg-field px-4 py-[11px] text-sm tracking-[0.16px] text-foreground transition-colors outline-none placeholder:text-subtle-foreground focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:border-transparent disabled:text-subtle-foreground aria-invalid:outline-2 aria-invalid:-outline-offset-2 aria-invalid:outline-destructive-foreground",
        className
      )}
      {...props}
    />
  )
}

export { Textarea }
