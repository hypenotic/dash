<template>
    <section>
        <div class="container">
            <div class="columns">
                <div class="column is-two-thirds">  
                    <h1 class="is-size-1 vcenter has-text-centered">Welcome to the Dashboard</h1>
                </div>
                <div class="column">
                    <div class="vcenter">
                        <form @submit.prevent="login({ username, password })">
                            <div class="field">
                                <p class="control has-icons-left has-icons-right">
                                    <input class="input is-medium" type="username" placeholder="Username" autocomplete="hypedash-username" v-model="username">
                                    <span class="icon is-small is-left"><i class="fa fa-envelope"></i></span>
                                    <span class="icon is-small is-right"><i class="fa fa-check"></i></span>
                                </p>
                            </div>
                            <div class="field">
                                <p class="control has-icons-left">
                                    <input class="input is-medium" type="password" placeholder="Password" autocomplete="off" v-model="password">
                                    <span class="icon is-small is-left">
                                        <i class="fa fa-lock"></i>
                                    </span>
                                </p>
                            </div>
                            <div class="field">
                                <p class="control">
                                    <button class="button is-medium">
                                        Login
                                    </button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    export default {
        data() {
            return {
                username: "",
                password: ""
            }
        },
        methods: {
            login() {
                this.$store.dispatch("login", {
                    username: this.username,
                    password: this.password
                }).then(() => {
                    this.$router.push("/admin")
                });
            }
        },
        head() {
            return {
                title: null,
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

        }
    }
</script>

<style scoped>
    section {
        background-color: #7957d5;
        color: white;
    }

    .columns {
        height: 100vh;
        margin: 0;
    }

    .vcenter {
        position: relative;
        top: 50%;
        transform: translateY(-50%);
    }
</style>