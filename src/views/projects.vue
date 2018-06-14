<template>
    <div>
        <div v-for="project in projects">
            <h1 v-html="project.title.rendered"></h1>
            <p v-html="project.excerpt.rendered"></p>
            <ul>
                <li v-for="client in project.clients">{{client}}</li>        
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
            }
        },
        computed: {
            projects: function () {
                return this.$store.state.moduleProject.projects
            }
        },
        methods: {
            addUser() {
                // Send an event upwards to be picked up by App
                
            }
        },
        head() {
            return {
                title: this.$nuxt.$store.state.moduleUser.name,
                meta: [{
                        hid: 'og:title',
                        name: 'og:title',
                        content: this.title
                    },
                    {
                        hid: 'og:title',
                        name: 'og:title',
                        content: this.title
                    },
                    {
                        hid: 'description',
                        name: 'description',
                        content: 'My custom description'
                    }
                ]
            }
        },
        created() {
            this.$store.dispatch('moduleProject/popProjects')
        }
    }
</script>

<style>
</style>