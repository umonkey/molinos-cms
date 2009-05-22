<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/xsl/list.xsl" />

  <xsl:template match="data[../@name='list' and ../@preset='taxonomy']" mode="nodelist">
    <ul class="nodes">
      <xsl:apply-templates select="node" mode="tagtree" />
    </ul>
  </xsl:template>

  <!-- вывод разделов -->
  <xsl:template match="node" mode="tagtree">
    <li>
      <xsl:attribute name="class">
        <xsl:if test="@published">
          <xsl:text> published</xsl:text>
        </xsl:if>
        <xsl:if test="not(@published)">
          <xsl:text> unpublished</xsl:text>
        </xsl:if>
        <xsl:if test="position() = last()">
          <xsl:text> last</xsl:text>
        </xsl:if>
        <xsl:if test="children/node">
          <xsl:text> with-children</xsl:text>
        </xsl:if>
      </xsl:attribute>
      
      <xsl:if test="children/node">
        <span class="expand-collapse" />
      </xsl:if>
      
      <span class="container">
        <!--
        <a title="Найти все документы из этого раздела" href="admin/content/list?search=tags%3A{@id}">
        -->
        <a href="admin/node/{@id}">
          <xsl:attribute name="class">
          <xsl:text>picker</xsl:text>
            <xsl:if test="not(@published)">
              <xsl:text> unpublished</xsl:text>
            </xsl:if>
          </xsl:attribute>
          <xsl:value-of select="@name" />
          <xsl:if test="not(@name)">
            <xsl:text>(без названия)</xsl:text>
          </xsl:if>
        </a>
        <span class="actions">
          <input type="checkbox" name="selected[]" value="{@id}" />
          <a title="Добавить подраздел" class="icon tag-add" href="admin/create/tag/{@id}?destination={$back}"></a>
          <xsl:if test="position() != 1"><a title="Поднять раздел" class="icon tag-up" href="nodeapi.rpc?action=raise&amp;node={@id}&amp;destination={/page/@back}"></a></xsl:if>
          <xsl:if test="position() != last()"><a title="Опустить раздел" class="icon tag-down" href="nodeapi.rpc?action=sink&amp;node={@id}&amp;destination={/page/@back}"></a></xsl:if>
          <a title="Изменить свойства раздела" class="icon tag-edit" href="admin/edit/{@id}?destination={/page/@back}"></a>
        </span>
      </span>
      <xsl:if test="children/node">
        <ul>
          <xsl:apply-templates select="children/node" mode="tagtree" />
        </ul>
      </xsl:if>
    </li>
  </xsl:template>
</xsl:stylesheet>
