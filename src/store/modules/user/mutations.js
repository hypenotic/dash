//mutations are committed by actions to modify the state

const mutations = {
    /*
    setMyThing: (state, payload) => {
        state.myThing = payload;
    }
    */
    
    setName: (state, payload) => {
        state.name = payload;  
    },
    
    setEmail: (state, payload) => {
        state.email = payload;  
    },
    
    setClients: (state, payload) => {
        state.clients = payload;
    },
    
    addClient: (state, payload) => {
        state.clients.push(payload);
    }
    
}

export default mutations;