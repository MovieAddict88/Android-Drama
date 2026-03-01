package short.drama.ronald.com.model;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class Drama {

    @SerializedName("bookId")
    private String bookId;

    @SerializedName("bookName")
    private String bookName;

    @SerializedName("coverWap")
    private String coverWap;

    @SerializedName("cover")
    private String cover;

    @SerializedName("chapterCount")
    private int chapterCount;

    @SerializedName("introduction")
    private String introduction;

    @SerializedName("tags")
    private List<String> tags;

    @SerializedName("tagNames")
    private List<String> tagNames;

    @SerializedName("protagonist")
    private String protagonist;

    @SerializedName("shelfTime")
    private String shelfTime;

    @SerializedName("rankVo")
    private RankVo rankVo;

    public String getBookId() { return bookId; }
    public String getBookName() { return bookName; }

    public String getCoverUrl() {
        if (coverWap != null && !coverWap.isEmpty()) return coverWap;
        return cover;
    }

    public int getChapterCount() { return chapterCount; }
    public String getIntroduction() { return introduction; }

    public List<String> getTags() {
        if (tags != null && !tags.isEmpty()) return tags;
        return tagNames;
    }

    public String getProtagonist() { return protagonist; }
    public String getShelfTime() { return shelfTime; }
    public RankVo getRankVo() { return rankVo; }

    public static class RankVo {
        @SerializedName("hotCode")
        private String hotCode;

        public String getHotCode() { return hotCode; }
    }
}
