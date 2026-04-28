import{$ as t}from"./index-DuK2Ugk2.js";const r="favorites",s={index(){return t.get(`${r}`)},store(e){return t.post(`${r}`,{product_id:e})},delete(e){return t.delete(`${r}/${e}`)}};export{s as F};
