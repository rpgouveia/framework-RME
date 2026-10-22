import * as RadioGroupPrimitive from "@radix-ui/react-radio-group"
import * as React from "react"

import { cn } from "@/lib/utils"

function RadioGroup({
  className,
  ...props
}: React.ComponentProps<typeof RadioGroupPrimitive.Root>) {
  return (
    <RadioGroupPrimitive.Root
      data-slot="radio-group"
      className={cn("grid gap-2", className)}
      {...props}
    />
  )
}

function RadioGroupItem({
  className,
  ...props
}: React.ComponentProps<typeof RadioGroupPrimitive.Item>) {
  return (
    <RadioGroupPrimitive.Item
      data-slot="radio-group-item"
      className={cn(
        "border-foreground aspect-square size-4 shrink-0 rounded-full border outline-none focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-ring disabled:cursor-not-allowed disabled:border-subtle-foreground data-[state=checked]:border-[5px]",
        className
      )}
      {...props}
    />
  )
}

/**
 * A radio as a bordered tile with a title and a description, for choices
 * that need explaining. A disabled tile stays visible with its reason.
 */
function RadioTile({
  className,
  title,
  description,
  reason,
  ...props
}: React.ComponentProps<typeof RadioGroupPrimitive.Item> & {
  title: React.ReactNode
  description?: React.ReactNode
  reason?: React.ReactNode
}) {
  return (
    <RadioGroupPrimitive.Item
      data-slot="radio-tile"
      className={cn(
        "group bg-muted grid w-full grid-cols-[16px_1fr] gap-3 border border-transparent p-4 text-left outline-none focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring data-[state=checked]:border-foreground disabled:cursor-not-allowed",
        className
      )}
      {...props}
    >
      <span className="border-foreground mt-0.5 size-4 rounded-full border group-data-[state=checked]:border-[5px] group-disabled:border-subtle-foreground" />
      <span className="grid gap-1">
        <span className="font-semibold group-disabled:text-subtle-foreground">
          {title}
        </span>
        {description && (
          <span className="text-muted-foreground text-xs tracking-[0.32px]">
            {description}
          </span>
        )}
        {reason && (
          <span className="text-tag-yellow-foreground text-xs tracking-[0.32px]">
            {reason}
          </span>
        )}
      </span>
    </RadioGroupPrimitive.Item>
  )
}

export { RadioGroup, RadioGroupItem, RadioTile }
