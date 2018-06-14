<template>
<div>
    <h1>{{name}}</h1>
    <p>{{email}}</p>
    <p>{{clients}}</p>
    </div>
</template>

<script>

    export default {
        data () {
            return {
                name: this.$store.state.moduleUser.name,
                email: this.$store.state.moduleUser.email,
                clients: this.$store.state.moduleUser.clients
            }
        },
        methods: {
            addUser () {
                // Send an event upwards to be picked up by App
                this.$dispatch('button-pressed')
            }
        },
        head () {
            return {
                title: this.$nuxt.$store.state.moduleUser.name,
                meta: [
                    { hid: 'og:title', name: 'og:title', content: this.title },
                    { hid: 'og:title', name: 'og:title', content: this.title },
                    { hid: 'description', name: 'description', content: 'My custom description' }
                ]
            }
        },
        created() {

        }
    }
</script>

<style>


</style>