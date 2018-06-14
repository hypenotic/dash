<template>
    <div>
        <users></users>
        <add-project></add-project>
        <projects></projects>
    </div>
</template>

<script>

    import Users from './views/users.vue'
    import AddProject from './views/addproject.vue'
    import Projects from './views/projects.vue'
    //import Users from './components/users'
    
    export default {
        components: {
            Users,
            Projects,
            AddProject,
            //Clients
        },
        data () {
            return {
                title: this.$store.state.moduleUser.name,
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