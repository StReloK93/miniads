<template>
   <div>
      <h1>Admin Layout</h1>
      <BaseForm />
      <RouterView />
   </div>
</template>

<script setup lang="ts">
import BaseForm from "@/components/BaseForm.vue";
import axios from "axios";

axios.get("api/categories").then((response) => {
   console.log("Categories:", response.data);
});
</script>
