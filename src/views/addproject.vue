<template>
    <div>
        <form>
            <input name="projecttitle" placeholder="Project Title" type="text">
            <input name="projectexcerpt" placeholder="Project Excerpt" type="text">
            <input name="projectclient" placeholder="Project Client" type="text">
            <button name="Submit" v-on:click="">Add new Project</button>
        </form>
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