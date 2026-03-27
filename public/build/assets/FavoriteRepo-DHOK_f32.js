import{a7 as t}from"./index-CIDqEXTC.js";const r="favorites",s={index(){return t.get(`${r}`)},store(e){return t.post(`${r}`,{product_id:e})},delete(e){return t.delete(`${r}/${e}`)}};export{s as F};
