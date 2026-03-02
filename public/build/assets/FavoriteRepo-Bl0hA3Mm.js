import{V as t}from"./index-BYjx1Fp_.js";const r="favorites",s={index(){return t.get(`${r}`)},store(e){return t.post(`${r}`,{product_id:e})},delete(e){return t.delete(`${r}/${e}`)}};export{s as F};
