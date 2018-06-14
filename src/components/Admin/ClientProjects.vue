<template>
	<div>
	<hype-header></hype-header>
		<div class="wrapper">
			<div class="sidebar">
			<b-tooltip label="Add New Project" position="is-bottom">
				<button class="button is-primary" @click="addClient"><i class="fas fa-plus fa-3x"></i></button>
			</b-tooltip>
			</div>

			<div class="cards">
				<div class="card" v-for="project in projects">
					<header class="card-header">
						<p class="card-header-title">{{ project.name }}</p>
					</header>
					<div class="card-content">
						<div class="content">
							{{project.desc}}
							<br>
							<time datetime="2016-1-1">Last Updated: Jan 27, 2018 </time>
						</div>
					</div>
					<footer class="card-footer">
						<a href="#" class="card-footer-item">
							<b-tooltip label="Edit Project" position="is-top"><i class="fas fa-pencil-alt"></i></b-tooltip>
						</a>
						<a href="#" class="card-footer-item" @click="removeProject(project); success(project.name);">
							<b-tooltip label="Archive Project" position="is-top" ><i class="fas fa-archive"></i></b-tooltip>
						</a>
						<a href="#" class="card-footer-item">
							<b-tooltip label="Delete Project" position="is-top"><i class="fas fa-trash"></i></b-tooltip>
						</a>
					</footer>
				</div>
			</div>
		</div>
		<hype-footer></hype-footer>
	</div>
</template>


<script>
	import Header from './Header.vue';
	import { mapActions } from 'vuex';
	export default {
		components: {
			'hype-header': Header,
		},
		data() {
            return {
				name: this.$store.state.name,
				clientName: '',
				clientDesc: '',
				projects: this.$store.state.projects,
            }
		},
		methods: {
			...mapActions([
				'removeProject'
			]),
            addClient() {
				if(this.clientName != '' && this.clientDesc != '') {
					this.clients.unshift({
						name: this.clientName,
						desc: this.clientDesc
						}); 
					this.clientName = '';
					this.clientDesc = '';
					//console.log(this.clients)
				}
            },
			success(project) {
                this.$toast.open({
                    message: project + ' archived successfully!',
					type: 'is-success',
					duration: 2000,
				})
			},
        }
	};
</script>

<style scoped>

.wrapper {
	margin: 30px;
	display: grid;
	grid-template-columns: 100px 1fr;
	grid-template-areas: "sidebar content" "sidebar .";
	grid-gap: 20px;
}

.sidebar {
	grid-area: sidebar;
}

.cards {
	display: grid;
	grid-area: content;
	grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
	grid-gap: 20px;
}

button {
	height: 100px;
	width: 80px;
}

time {
	display: block;
	font-size: .815rem;
	margin-top: 1rem;
}



</style>