const endpoint = "http://dash.test/cms/wp-json";

import Vue from 'vue';
import Vuex from 'vuex';
import axios from 'axios';

Vue.use(Vuex);

const login = "login";

export const store = new Vuex.Store({ 
    state: { 
        userToken: "unset",
        isAuthenticated: false,
        name: 'Pleebnus',
        clients: [
            {name: 'Farmlink', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'}, 
            {name: 'Fiesta Farms', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'},
            {name: 'LGO', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'}, 
            {name: 'Ingenuity', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'},
            {name: 'Hypenotic', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'}, 
            {name: 'Park People', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'},
            {name: 'OneGrid', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'}
        ],
        projects: [
            {name: 'Project #1', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'},
            {name: 'Project #2', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'}, 
            {name: 'Project #3', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'}, 
            {name: 'Project #4', desc: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'}, 
        ]	
    },
    getters:{
        isAuthenticated(state){ return !!state.isAuthenticated }
    },
    mutations: {
        saveToken: (state, payload)=>{
            state.token = payload.userToken
            state.isAuthenticated = true;
            localStorage.setItem('localState', JSON.stringify(state));
        },
        addClient: (state, payload)=>{
            axios.post(
                endpoint + "/wp/v2/client",
                {
                    title: payload.title,
                    content: payload.content,
                    excerpt: payload.excerpt,
                },
                {
                    transformRequest: [(data) => JSON.stringify(data.data)],
                    headers: {
                        'Authentication': "Bearer " + state.token,
                    }
                }
            )
        },
        addProject: (state, payload)=>{
            fetch(endpoint + "/wp/v2/posts",{
                method: "POST",
                headers:{
                    'Content-Type': 'application/json',
                    'accept': 'application/json',
                    'Authorization': 'Bearer ' + state.token
                },
                body:JSON.stringify({
                    title: 'Lorem ipsum dolor sit amet',
                    content: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    status: 'publish'
                })
            }).then(function(response){
                return response.json();
            }).then(function(post){
                console.log(post);
            });
        },
        removeClient: (state, payload) => {
            let i = state.clients.indexOf(payload);
            if(i != -1) {
                state.clients.splice(i, 1);
            }
        },
        removeProject: (state, payload) => {
            let i = state.projects.indexOf(payload);
            if(i != -1) {
                state.projects.splice(i, 1);
            }
        },
    },
    actions: {
        login: ({commit}, payload) => {
            axios.post( endpoint + '/jwt-auth/v1/token', {
                username: payload.username,
                password: payload.password
            } )

                .then( function( response ) {
                console.log( response.data.token )
                commit('saveToken', response.data.token)
            } )

                .catch( function( error ) {
                console.error( 'Error', error );
                commit('saveToken', null)
            } );  
        },
        
        checkUser: ({ commit }) => {
            let check = localStorage.getItem('localState');
            if (check !== null) {
                
                var ls = JSON.parse(check);
                
                console.log(ls);
                console.log(ls);
                console.log(ls);
                
                fetch(endpoint + "/jwt-auth/v1/token/validate",{
                    method: "POST",
                    headers:{
                        'Authorization': 'Bearer ' //+ ls.userToken
                    }
                }).then(function(response){
                    return response.json();
                }).then(function(post){
                    console.log(post);
                });
            } else {
                console.log("you literally have not logged in");
                return;
            }
        },
        
        addClient: ({ commit }, payload) => {
            commit('addClient', payload)
        },
        addProject: ({ commit }, payload) => {
            commit('addProject', payload)
        },
        
        removeClient: ({ commit }, payload) => {
            commit('removeClient', payload)
        },
        removeProject: ({ commit }, payload) => {
            commit('removeProject', payload)
        }

    }
});