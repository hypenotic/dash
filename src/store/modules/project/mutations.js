//mutations are committed by actions to modify the state

const mutations = {
    /*
    setMyThing: (state, payload) => {
        state.myThing = payload;
    }
    */
    
    setProjects: (state, payload) => {
        state.projects = payload;
    },
    
    addProject: (state, payload) => {
        state.projects.push(payload);
    }
    
}

export default mutations;