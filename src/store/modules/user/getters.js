//getters serve information about the state to the app

const getters = {
    /*
    getMyTHing: state => {
        return state.myThing;
    }
    */
    
    getName: state => {
        return state.name;
    },
    
    getEmail: state => {
        return state.email;
    },
    
    getClients: state => {
        return state.clients;
    }
    
}

export default getters;