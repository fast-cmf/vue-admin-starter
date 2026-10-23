<script setup lang="ts">
import type { PaginationListProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { cn } from "cn"
import { PaginationList } from "reka-ui"

const props = defineProps<PaginationListProps & {
  class?: HTMLAttributes["class"]
}>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <PaginationList
    data-slot="pagination-list"
    v-bind="delegatedProps"
    :class="cn('flex flex-row items-center gap-1', props.class)"
    v-slot="slotProps"
  >
    <slot v-bind="slotProps" />
  </PaginationList>
</template>
