var Notice={
  getPage : function (i)
  {
    var $messagebox = $("#messagebox");
    var noticetype = $messagebox.children("input.noticetype:hidden").val();
    $messagebox.empty();
  	LoadComponent("messagebox", $messagebox.attr("getnoticeurl")+"/"+ noticetype +"/"+i.toString());
  },
  
  toShowDetail:function (e)
  {
    var bulletin_id = $(e).parent().parent().parent().siblings("input.bulletin_id:hidden").val()
    var $messagebox = $("#messagebox");
    var re_type =  $messagebox.children("input.noticetype:hidden").val();
    var re_pageindex = $messagebox.children("input.pageindex:hidden").val();
    
    $messagebox.empty();
    
    LoadComponent("messagebox", 
      $messagebox.attr("detailurl")+"/"+bulletin_id.toString(), 
      {
        re_type: re_type, 
        re_pageindex: re_pageindex
      });
  },
  
  returnPrePage: function()
  {
    var $messagebox = $("#messagebox");
    var re_type = $messagebox.children("input.re_type:hidden").val();
    var re_pageindex = $messagebox.children("input.re_pageindex:hidden").val();
    
    $messagebox.empty();
    LoadComponent('messagebox', $messagebox.attr("getnoticeurl")+"/"+ re_type +"/"+re_pageindex);
  },
  
  gotoLastMsg : function() 
  {
    var $messagebox = $("#messagebox");
    
    var lastone = $messagebox.children("input.lastone:hidden").val();
    var re_type = $messagebox.children("input.re_type:hidden").val();
    var re_pageindex = $messagebox.children("input.re_pageindex:hidden").val();
    
    $messagebox.empty();
    
    LoadComponent("messagebox", 
      $messagebox.attr("detailurl")+"/"+lastone, 
      {
        re_type: re_type, 
        re_pageindex: re_pageindex
      });
  },
  
  gotoNextMsg : function() 
  {
    var $messagebox = $("#messagebox");
    
    var nextone = $messagebox.children("input.nextone:hidden").val();
    var re_type = $messagebox.children("input.re_type:hidden").val();
    var re_pageindex = $messagebox.children("input.re_pageindex:hidden").val();
    
    $messagebox.empty();
    
    LoadComponent("messagebox", 
      $messagebox.attr("detailurl")+"/"+nextone, 
      {
        re_type: re_type, 
        re_pageindex: re_pageindex
      });
  }
  
};