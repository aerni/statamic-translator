<template>
    <div
        :class="{
            'bg-yellow-50' : type === 'alert',
            'bg-red-50' : type === 'error',
            'bg-blue-50' : type === 'info',
            'bg-blue-50' : type === 'loading',
            'bg-green-50' : type === 'success',
        }"
        class="rounded-md p-2" 
    >
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg v-if="type === 'alert'" class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <svg v-if="type === 'error'" class="h-4 w-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <svg v-if="type === 'info'" class="h-4 w-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span v-if="type === 'loading'" class="icon icon-circular-graph animation-spin"></span>
                <svg v-if="type === 'success'" class="h-4 w-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-2">
                <h3 
                    :class="{
                        'text-yellow-800' : type === 'alert',
                        'text-red-800' : type === 'error',
                        'text-blue-800' : type === 'info',
                        'text-blue-800' : type === 'loading',
                        'text-green-800' : type === 'success',
                    }"
                    class="text-sm leading-tight font-bold"
                >
                    {{ title }}
                </h3>
                <p 
                    v-if="text" 
                    :class="{
                        'text-yellow-700' : type === 'alert',
                        'text-red-700' : type === 'error',
                        'text-blue-700' : type === 'info',
                        'text-blue-700' : type === 'loading',
                        'text-green-700' : type === 'success',
                    }"
                    class="text-sm leading-tight"
                >
                    {{ text }}
                </p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['title', 'text', 'type'],
}
</script>