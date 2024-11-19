<template>
    <div>
      <div ref="summernote" class="summernote"></div>
    </div>
  </template>
  
  <script>
  import $ from 'jquery';
  
  export default {
    props: {
      modelValue: {
        type: String,
        default: '',
      },
    },
    emits: ['update:modelValue'],
    mounted() {
      const vm = this;
      $(this.$refs.summernote).summernote({
        height: 150,
        callbacks: {
          onChange(contents) {
            vm.$emit('update:modelValue', contents);
          },
        },
      }).summernote('code', this.modelValue);
    },
    watch: {
      modelValue(newValue) {
        $(this.$refs.summernote).summernote('code', newValue);
      },
    },
    beforeUnmount() {
      $(this.$refs.summernote).summernote('destroy');
    },
  };
  </script>